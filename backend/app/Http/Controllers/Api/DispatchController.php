<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DispatchService;
use App\Models\ServiceOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DispatchController extends Controller
{
    public function __construct(
        private readonly DispatchService $dispatch
    ) {}

    /**
     * GET /api/dispatch/queue
     * Fila de chamados ativos ordenada por prioridade + SLA.
     */
    public function queue(): JsonResponse
    {
        $orders = $this->dispatch->getQueue(auth()->user()->tenant_id);
        return response()->json($orders);
    }

    /**
     * POST /api/dispatch/{orderId}/assign
     * Atribui um mecânico a um chamado (manual ou automático).
     */
    public function assign(Request $request, string $orderId): JsonResponse
    {
        $request->validate([
            'technician_id' => ['nullable', 'uuid', 'exists:technicians,id'],
        ]);

        $order = ServiceOrder::findOrFail($orderId);

        $updated = $this->dispatch->assign($order, $request->technician_id);

        return response()->json($updated);
    }

    /**
     * POST /api/dispatch/{orderId}/unassign
     * Remove a atribuição de um chamado.
     */
    public function unassign(string $orderId): JsonResponse
    {
        $order   = ServiceOrder::findOrFail($orderId);
        $updated = $this->dispatch->unassign($order);

        return response()->json($updated);
    }
}
