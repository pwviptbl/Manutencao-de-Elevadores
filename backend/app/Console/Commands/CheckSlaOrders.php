<?php

namespace App\Console\Commands;

use App\Events\ServiceOrderUpdated;
use App\Models\ServiceOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckSlaOrders extends Command
{
    protected $signature   = 'orders:check-sla';
    protected $description = 'Marca chamados com SLA vencido e notifica via WebSocket';

    public function handle(): int
    {
        // Percorre todos os tenants que têm chamados ativos sem violação ainda
        $tenantIds = ServiceOrder::query()
            ->whereNull('sla_violated_at')
            ->whereNotNull('sla_deadline')
            ->where('sla_deadline', '<', now())
            ->whereIn('status', ['open', 'assigned', 'in_progress'])
            ->distinct()
            ->pluck('tenant_id');

        $total = 0;

        foreach ($tenantIds as $tenantId) {
            // Ativa o RLS para o tenant atual
            DB::statement(
                "SELECT set_config('app.tenant_id', ?, false)",
                [(string) $tenantId]
            );

            $violated = ServiceOrder::query()
                ->whereNull('sla_violated_at')
                ->whereNotNull('sla_deadline')
                ->where('sla_deadline', '<', now())
                ->whereIn('status', ['open', 'assigned', 'in_progress'])
                ->get();

            foreach ($violated as $order) {
                $order->update(['sla_violated_at' => now()]);
                broadcast(new ServiceOrderUpdated($order->load(['condominium', 'elevator', 'technician'])));
                $total++;
            }
        }

        $this->info("SLA verificado. {$total} chamado(s) marcado(s) como violado(s).");

        return self::SUCCESS;
    }
}
