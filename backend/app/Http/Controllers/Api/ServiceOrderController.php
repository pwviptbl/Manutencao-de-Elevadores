<?php

namespace App\Http\Controllers\Api;

use App\Enums\ServiceOrderPriority;
use App\Enums\ServiceOrderStatus;
use App\Events\ServiceOrderCreated;
use App\Events\ServiceOrderUpdated;
use App\Http\Controllers\Controller;
use App\Models\ServiceOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceOrderController extends Controller
{
    /**
     * GET /api/service-orders
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status'         => ['nullable', 'string'],
            'priority'       => ['nullable', 'string'],
            'technician_id'  => ['nullable', 'uuid'],
            'condominium_id' => ['nullable', 'uuid'],
            'elevator_id'    => ['nullable', 'uuid'],
            'origin'         => ['nullable', 'string'],
            'search'         => ['nullable', 'string', 'max:100'],
            'from'           => ['nullable', 'date'],
            'to'             => ['nullable', 'date'],
            'per_page'       => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = ServiceOrder::with([
            'elevator:id,serial_number,manufacturer,model',
            'condominium:id,name,city,state',
            'assignedTechnician:id,name,phone',
            'createdByUser:id,name',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('technician_id')) {
            $query->where('assigned_technician_id', $request->technician_id);
        }

        if ($request->filled('condominium_id')) {
            $query->where('condominium_id', $request->condominium_id);
        }

        if ($request->filled('elevator_id')) {
            $query->where('elevator_id', $request->elevator_id);
        }

        if ($request->filled('origin')) {
            $query->where('origin', $request->origin);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%")
                  ->orWhere('caller_name', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to . ' 23:59:59');
        }

        $orders = $query
            ->orderByRaw("CASE priority WHEN 'P0' THEN 4 WHEN 'P1' THEN 3 WHEN 'P2' THEN 2 ELSE 1 END DESC")
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 25);

        return response()->json($orders);
    }

    /**
     * POST /api/service-orders
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'elevator_id'    => ['required', 'uuid', 'exists:elevators,id'],
            'condominium_id' => ['required', 'uuid', 'exists:condominiums,id'],
            'priority'       => ['required', 'string', 'in:P0,P1,P2,P3'],
            'type'           => ['required', 'string', 'in:corrective,preventive,emergency'],
            'title'          => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string', 'max:5000'],
            'caller_name'    => ['nullable', 'string', 'max:100'],
            'caller_phone'   => ['nullable', 'string', 'max:20'],
        ]);

        $priority     = ServiceOrderPriority::from($data['priority']);
        $tenantId     = auth()->user()->tenant_id;

        $order = ServiceOrder::create([
            ...$data,
            'tenant_id'          => $tenantId,
            'status'             => ServiceOrderStatus::Open,
            'origin'             => 'panel',
            'created_by_user_id' => auth()->id(),
            'sla_deadline'       => now()->addHours($priority->slaHours()),
        ]);

        $order->load([
            'elevator:id,serial_number,manufacturer',
            'condominium:id,name',
            'createdByUser:id,name',
        ]);

        broadcast(new ServiceOrderCreated($order))->toOthers();

        return response()->json($order, 201);
    }

    /**
     * GET /api/service-orders/{id}
     */
    public function show(string $id): JsonResponse
    {
        $order = ServiceOrder::with([
            'elevator.condominium',
            'condominium',
            'assignedTechnician',
            'createdByUser',
            'activities.causer',
        ])->findOrFail($id);

        return response()->json($order);
    }

    /**
     * PUT /api/service-orders/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $order = ServiceOrder::findOrFail($id);

        $data = $request->validate([
            'priority'             => ['nullable', 'string', 'in:P0,P1,P2,P3'],
            'assigned_technician_id' => ['nullable', 'uuid', 'exists:technicians,id'],
            'title'                => ['nullable', 'string', 'max:255'],
            'description'          => ['nullable', 'string', 'max:5000'],
            'resolution_notes'     => ['nullable', 'string', 'max:5000'],
            'checklist'            => ['nullable', 'array'],
        ]);

        $order->update($data);

        broadcast(new ServiceOrderUpdated($order))->toOthers();

        return response()->json($order);
    }

    /**
     * PATCH /api/service-orders/{id}/status
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string'],
        ]);

        $order  = ServiceOrder::findOrFail($id);
        $next   = ServiceOrderStatus::from($request->status);

        $order->transitionTo($next, auth()->user());

        broadcast(new ServiceOrderUpdated($order->fresh()))->toOthers();

        return response()->json($order->fresh());
    }

    /**
     * DELETE /api/service-orders/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $order = ServiceOrder::findOrFail($id);
        $order->delete();

        return response()->json(null, 204);
    }
}
