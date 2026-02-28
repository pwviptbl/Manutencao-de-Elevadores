<?php

namespace App\Http\Controllers\Public\V1;

use App\Enums\ServiceOrderPriority;
use App\Enums\ServiceOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\ServiceOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Pública v1 — Chamados / Ordens de Serviço
 *
 * Endpoints:
 *   GET    /api/v1/orders             → index()
 *   POST   /api/v1/orders             → store()
 *   GET    /api/v1/orders/{id}        → show()
 *   PUT    /api/v1/orders/{id}        → update()
 *   PATCH  /api/v1/orders/{id}/status → updateStatus()
 *   DELETE /api/v1/orders/{id}        → destroy()
 */
class OrderController extends Controller
{
    /**
     * GET /api/v1/orders
     * Lista chamados do tenant com filtros e paginação cursor-based.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status'         => ['nullable', 'string'],
            'priority'       => ['nullable', 'string', 'in:P0,P1,P2,P3'],
            'elevator_id'    => ['nullable', 'uuid'],
            'condominium_id' => ['nullable', 'uuid'],
            'from'           => ['nullable', 'date'],
            'to'             => ['nullable', 'date'],
            'per_page'       => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor'         => ['nullable', 'string'],
        ]);

        $query = ServiceOrder::with([
            'elevator:id,serial_number,manufacturer,model',
            'condominium:id,name,city,state',
            'assignedTechnician:id,name',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('elevator_id')) {
            $query->where('elevator_id', $request->elevator_id);
        }

        if ($request->filled('condominium_id')) {
            $query->where('condominium_id', $request->condominium_id);
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to . ' 23:59:59');
        }

        $orders = $query
            ->orderBy('created_at', 'desc')
            ->cursorPaginate($request->per_page ?? 25);

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'per_page'      => $orders->perPage(),
                'next_cursor'   => $orders->nextCursor()?->encode(),
                'prev_cursor'   => $orders->previousCursor()?->encode(),
            ],
        ]);
    }

    /**
     * POST /api/v1/orders
     * Cria novo chamado via API externa. Suporta Idempotency-Key.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'elevator_id'    => ['required', 'uuid', 'exists:elevators,id'],
            'condominium_id' => ['required', 'uuid', 'exists:condominiums,id'],
            'priority'       => ['required', 'string', 'in:P0,P1,P2,P3'],
            'type'           => ['required', 'string', 'in:corrective,preventive,emergency'],
            'description'    => ['required', 'string', 'max:5000'],
            'contact_name'   => ['nullable', 'string', 'max:100'],
            'contact_phone'  => ['nullable', 'string', 'max:20'],
            'external_ref'   => ['nullable', 'string', 'max:100'],
        ]);

        /** @var \App\Models\ApiKey $apiKey */
        $apiKey = $request->attributes->get('api_key');

        $priority = ServiceOrderPriority::from($data['priority']);

        $order = ServiceOrder::create([
            ...$data,
            'tenant_id'    => $apiKey->tenant_id,
            'status'       => ServiceOrderStatus::Open,
            'origin'       => 'api',
            'sla_deadline' => now()->addHours($priority->slaHours()),
        ]);

        $order->load(['elevator:id,serial_number', 'condominium:id,name']);

        return response()->json(['data' => $order], 201);
    }

    /**
     * GET /api/v1/orders/{id}
     */
    public function show(string $id): JsonResponse
    {
        $order = ServiceOrder::with([
            'elevator.condominium',
            'assignedTechnician:id,name,phone',
        ])->findOrFail($id);

        return response()->json(['data' => $order]);
    }

    /**
     * PUT /api/v1/orders/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $order = ServiceOrder::findOrFail($id);

        $data = $request->validate([
            'description'   => ['nullable', 'string', 'max:5000'],
            'contact_name'  => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'external_ref'  => ['nullable', 'string', 'max:100'],
        ]);

        $order->update($data);

        return response()->json(['data' => $order]);
    }

    /**
     * PATCH /api/v1/orders/{id}/status
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string'],
        ]);

        $order = ServiceOrder::findOrFail($id);
        $next  = ServiceOrderStatus::from($request->status);

        $order->transitionTo($next, null, source: 'api');

        return response()->json(['data' => $order->fresh()]);
    }

    /**
     * DELETE /api/v1/orders/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        ServiceOrder::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
