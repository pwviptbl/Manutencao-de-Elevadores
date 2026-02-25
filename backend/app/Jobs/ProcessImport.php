<?php

namespace App\Jobs;

use App\Events\ImportProgressUpdated;
use App\Models\Condominium;
use App\Models\Elevator;
use App\Models\ImportJob;
use App\Models\Technician;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessImport implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 600; // 10 minutos

    public function __construct(
        public readonly string $importJobId
    ) {}

    public function handle(): void
    {
        $import = ImportJob::findOrFail($this->importJobId);

        // Injeta contexto RLS
        DB::statement(
            "SELECT set_config('app.tenant_id', ?, false)",
            [(string) $import->tenant_id]
        );

        $import->update(['status' => 'processing', 'started_at' => now()]);
        broadcast(new ImportProgressUpdated($import->fresh()));

        try {
            $path = Storage::path($import->file_path);
            $rows = $this->readFile($path);

            $import->update(['total_rows' => count($rows)]);

            $errors     = [];
            $processed  = 0;
            $errorCount = 0;
            $batchSize  = 500;

            foreach ($rows->chunk($batchSize) as $chunk) {
                foreach ($chunk as $index => $row) {
                    $rowNumber = $index + 2; // +2 porque a linha 1 é o cabeçalho

                    try {
                        $this->processRow($import->type, $import->tenant_id, $row, $rowNumber);
                        $processed++;
                    } catch (\Throwable $e) {
                        $errorCount++;
                        $errors[] = [
                            'row'     => $rowNumber,
                            'data'    => $row,
                            'error'   => $e->getMessage(),
                        ];
                    }
                }

                $import->update([
                    'processed_rows' => $processed,
                    'error_rows'     => $errorCount,
                    'errors'         => $errors,
                ]);

                broadcast(new ImportProgressUpdated($import->fresh()));
            }

            $import->update([
                'status'      => 'done',
                'finished_at' => now(),
            ]);

        } catch (\Throwable $e) {
            Log::error('ImportJob falhou', [
                'import_id' => $import->id,
                'error'     => $e->getMessage(),
            ]);

            $import->update([
                'status'      => 'failed',
                'finished_at' => now(),
                'errors'      => [['row' => 0, 'error' => $e->getMessage()]],
            ]);
        }

        broadcast(new ImportProgressUpdated($import->fresh()));

        // Remove arquivo temporário
        Storage::delete($import->file_path);
    }

    // ─────────────────────────────────────────
    // Processamento por tipo
    // ─────────────────────────────────────────

    private function processRow(string $type, string $tenantId, array $row, int $rowNumber): void
    {
        match ($type) {
            'condominiums' => $this->processCondominium($tenantId, $row, $rowNumber),
            'elevators'    => $this->processElevator($tenantId, $row, $rowNumber),
            'technicians'  => $this->processTechnician($tenantId, $row, $rowNumber),
            default        => throw new \InvalidArgumentException("Tipo de importação desconhecido: {$type}"),
        };
    }

    private function processCondominium(string $tenantId, array $row, int $rowNumber): void
    {
        $required = ['nome', 'endereço', 'cidade', 'estado', 'cep', 'telefone'];
        $this->assertRequired($row, $required, $rowNumber);

        Condominium::updateOrCreate(
            ['tenant_id' => $tenantId, 'cnpj' => $row['cnpj'] ?? null],
            [
                'tenant_id'  => $tenantId,
                'name'       => $row['nome'],
                'cnpj'       => $row['cnpj'] ?? null,
                'address'    => $row['endereço'],
                'city'       => $row['cidade'],
                'state'      => strtoupper($row['estado']),
                'zip_code'   => preg_replace('/\D/', '', $row['cep']),
                'phone'      => $row['telefone'] ?? null,
                'email'      => $row['email'] ?? null,
                'sla_hours'  => isset($row['sla_horas']) ? (int) $row['sla_horas'] : 4,
            ]
        );
    }

    private function processElevator(string $tenantId, array $row, int $rowNumber): void
    {
        $required = ['numero_serie', 'fabricante', 'condominio_cnpj'];
        $this->assertRequired($row, $required, $rowNumber);

        $condo = Condominium::where('tenant_id', $tenantId)
            ->where('cnpj', $row['condominio_cnpj'])
            ->first();

        if (! $condo) {
            throw new \RuntimeException("Condomínio com CNPJ '{$row['condominio_cnpj']}' não encontrado.");
        }

        Elevator::updateOrCreate(
            ['tenant_id' => $tenantId, 'serial_number' => $row['numero_serie']],
            [
                'tenant_id'          => $tenantId,
                'condominium_id'     => $condo->id,
                'serial_number'      => $row['numero_serie'],
                'manufacturer'       => $row['fabricante'],
                'model'              => $row['modelo'] ?? null,
                'floor_count'        => isset($row['andares']) ? (int) $row['andares'] : null,
                'last_revision_date' => $row['ultima_revisao'] ?? null,
            ]
        );
    }

    private function processTechnician(string $tenantId, array $row, int $rowNumber): void
    {
        $this->assertRequired($row, ['nome', 'telefone'], $rowNumber);

        Technician::updateOrCreate(
            ['tenant_id' => $tenantId, 'phone' => $row['telefone']],
            [
                'tenant_id' => $tenantId,
                'name'      => $row['nome'],
                'phone'     => $row['telefone'],
                'email'     => $row['email'] ?? null,
                'crea'      => $row['crea'] ?? null,
                'region'    => $row['regiao'] ?? null,
                'active'    => true,
            ]
        );
    }

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────

    private function readFile(string $path): Collection
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            $rows   = [];
            $handle = fopen($path, 'r');
            $header = null;

            while (($line = fgetcsv($handle, 0, ';')) !== false) {
                if ($header === null) {
                    $header = array_map('mb_strtolower', array_map('trim', $line));
                    continue;
                }
                $rows[] = array_combine($header, $line);
            }
            fclose($handle);

            return collect($rows);
        }

        // Excel (XLSX / XLS) via Maatwebsite
        $collection = Excel::toCollection(new class implements \Maatwebsite\Excel\Concerns\WithHeadingRow {}, $path);
        return $collection->first()?->map(fn($row) => array_map('trim', $row->toArray())) ?? collect();
    }

    private function assertRequired(array $row, array $fields, int $rowNumber): void
    {
        foreach ($fields as $field) {
            if (empty($row[$field])) {
                throw new \RuntimeException("Campo obrigatório '{$field}' está vazio na linha {$rowNumber}.");
            }
        }
    }
}
