<?php

namespace App\Services;

use App\Enums\ServiceOrderStatus;
use App\Enums\TechnicianStatus;
use App\Events\ServiceOrderUpdated;
use App\Models\ServiceOrder;
use App\Models\Technician;
use Illuminate\Support\Facades\DB;

class DispatchService
{
    /**
     * Atribui um mecânico a um chamado.
     * Se $technicianId for null, tenta atribuição automática.
     */
    public function assign(ServiceOrder $order, ?string $technicianId = null): ServiceOrder
    {
        $technician = $technicianId
            ? Technician::findOrFail($technicianId)
            : $this->findBestTechnician($order);

        if (! $technician) {
            throw new \DomainException('Nenhum mecânico disponível para atribuição automática.');
        }

        if (! $technician->isAvailable()) {
            throw new \DomainException("Mecânico {$technician->name} não está disponível.");
        }

        DB::transaction(function () use ($order, $technician) {
            $order->transitionTo(ServiceOrderStatus::Assigned);

            $order->update([
                'assigned_technician_id' => $technician->id,
                'assigned_at'            => now(),
            ]);

            $technician->update(['status' => TechnicianStatus::OnCall]);
        });

        broadcast(new ServiceOrderUpdated($order->fresh()))->toOthers();

        return $order->fresh(['assignedTechnician']);
    }

    /**
     * Remove a atribuição de um chamado (volta para open).
     */
    public function unassign(ServiceOrder $order): ServiceOrder
    {
        $technician = $order->assignedTechnician;

        DB::transaction(function () use ($order, $technician) {
            $order->transitionTo(ServiceOrderStatus::Open);
            $order->update([
                'assigned_technician_id' => null,
                'assigned_at'            => null,
            ]);

            // Libera o mecânico se não tiver outros chamados ativos
            if ($technician) {
                $hasOtherOrders = ServiceOrder::forTechnician($technician->id)
                    ->active()
                    ->where('id', '!=', $order->id)
                    ->exists();

                if (! $hasOtherOrders) {
                    $technician->update(['status' => TechnicianStatus::Available]);
                }
            }
        });

        broadcast(new ServiceOrderUpdated($order->fresh()))->toOthers();

        return $order->fresh();
    }

    /**
     * Retorna a fila de chamados ordenada por prioridade e SLA.
     */
    public function getQueue(string $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return ServiceOrder::with([
            'elevator:id,serial_number,manufacturer',
            'condominium:id,name,city,state',
            'assignedTechnician:id,name,status',
        ])
        ->whereIn('status', [
            ServiceOrderStatus::Open,
            ServiceOrderStatus::Assigned,
            ServiceOrderStatus::InProgress,
        ])
        ->orderByRaw("
            CASE priority
                WHEN 'P0' THEN 4
                WHEN 'P1' THEN 3
                WHEN 'P2' THEN 2
                ELSE 1
            END DESC
        ")
        ->orderBy('sla_deadline', 'asc')    // quem vai vencer o SLA primeiro
        ->orderBy('created_at', 'asc')
        ->get();
    }

    // ─────────────────────────────────────────
    // Internos
    // ─────────────────────────────────────────

    /**
     * Encontra o melhor mecânico disponível para o chamado.
     * Critério: disponível + mesma região do condomínio + menos chamados ativos.
     */
    private function findBestTechnician(ServiceOrder $order): ?Technician
    {
        $city = $order->condominium?->city ?? '';

        return Technician::available()
            ->withCount([
                'serviceOrders as active_count' => fn($q) => $q->active(),
            ])
            ->orderByRaw("
                CASE
                    WHEN region ILIKE ? THEN 0
                    ELSE 1
                END ASC
            ", ["%{$city}%"])
            ->orderBy('active_count', 'asc')
            ->first();
    }
}
