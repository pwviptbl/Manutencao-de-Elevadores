<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessImport;
use App\Models\ImportJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    /**
     * POST /api/imports
     * Recebe o arquivo e enfileira o job de processamento.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string', 'in:condominiums,elevators,technicians'],
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:10240'], // 10 MB
        ]);

        $file      = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $filename  = 'imports/' . Str::uuid() . '.' . $extension;

        Storage::put($filename, file_get_contents($file->getRealPath()));

        $import = ImportJob::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id'   => auth()->id(),
            'type'      => $request->type,
            'status'    => 'pending',
            'file_path' => $filename,
        ]);

        ProcessImport::dispatch($import->id)->onQueue('imports');

        return response()->json([
            'import_id' => $import->id,
            'status'    => $import->status,
            'message'   => 'Importação enfileirada. Acompanhe o progresso em tempo real via WebSocket.',
        ], 202);
    }

    /**
     * GET /api/imports/{id}
     * Status e erros de uma importação.
     */
    public function show(string $id): JsonResponse
    {
        $import = ImportJob::where('tenant_id', auth()->user()->tenant_id)
                           ->findOrFail($id);

        return response()->json([
            'id'             => $import->id,
            'type'           => $import->type,
            'status'         => $import->status,
            'total_rows'     => $import->total_rows,
            'processed_rows' => $import->processed_rows,
            'error_rows'     => $import->error_rows,
            'percent'        => $import->percent,
            'errors'         => $import->errors,
            'started_at'     => $import->started_at?->toIso8601String(),
            'finished_at'    => $import->finished_at?->toIso8601String(),
        ]);
    }

    /**
     * GET /api/imports
     * Histórico de importações do tenant.
     */
    public function index(): JsonResponse
    {
        $imports = ImportJob::where('tenant_id', auth()->user()->tenant_id)
            ->latest()
            ->limit(50)
            ->get(['id', 'type', 'status', 'total_rows', 'processed_rows',
                   'error_rows', 'started_at', 'finished_at', 'created_at']);

        return response()->json($imports);
    }

    /**
     * GET /api/imports/templates/{type}
     * Baixa o CSV template para uma categoria.
     */
    public function template(string $type): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $templates = [
            'condominiums' => [
                'filename' => 'template_condominios.csv',
                'headers'  => ['nome', 'cnpj', 'endereço', 'cidade', 'estado', 'cep', 'telefone', 'email', 'sla_horas'],
                'example'  => ['Condomínio Exemplo', '11.222.333/0001-44', 'Rua das Flores, 100', 'São Paulo', 'SP', '01401-001', '(11) 3000-0000', 'sindico@ex.com', '4'],
            ],
            'elevators' => [
                'filename' => 'template_elevadores.csv',
                'headers'  => ['numero_serie', 'fabricante', 'modelo', 'andares', 'condominio_cnpj', 'ultima_revisao'],
                'example'  => ['OTIS-2021-001', 'Otis', 'Gen2', '12', '11.222.333/0001-44', '2025-09-01'],
            ],
            'technicians' => [
                'filename' => 'template_mecanicos.csv',
                'headers'  => ['nome', 'telefone', 'email', 'crea', 'regiao'],
                'example'  => ['João da Silva', '(11) 99001-0001', 'joao@ex.com', 'CREA-SP 12345', 'Zona Sul SP'],
            ],
        ];

        if (! isset($templates[$type])) {
            abort(404, 'Template não encontrado.');
        }

        $template = $templates[$type];

        return response()->streamDownload(function () use ($template) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
            fputcsv($handle, $template['headers'], ';');
            fputcsv($handle, $template['example'], ';');
            fclose($handle);
        }, $template['filename'], [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
