<?php

namespace App\Http\Controllers\Api;

use App\Enums\TechnicianStatus;
use App\Http\Controllers\Controller;
use App\Models\Technician;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TechnicianController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status'   => ['nullable', 'string'],
            'region'   => ['nullable', 'string'],
            'search'   => ['nullable', 'string', 'max:100'],
            'active'   => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Technician::withCount([
            'serviceOrders as active_orders_count' => fn($q) => $q->active(),
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('region')) {
            $query->byRegion($request->region);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        return response()->json(
            $query->orderBy('name')->paginate($request->per_page ?? 25)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'phone'   => ['required', 'string', 'max:20'],
            'email'   => ['nullable', 'email', 'max:150'],
            'crea'    => ['nullable', 'string', 'max:30'],
            'region'  => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'uuid', 'exists:users,id'],
        ]);

        $technician = Technician::create([
            ...$data,
            'tenant_id' => auth()->user()->tenant_id,
        ]);

        return response()->json($technician, 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(
            Technician::withCount([
                'serviceOrders as total_orders_count',
                'serviceOrders as active_orders_count' => fn($q) => $q->active(),
            ])->findOrFail($id)
        );
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $technician = Technician::findOrFail($id);

        $data = $request->validate([
            'name'    => ['sometimes', 'string', 'max:100'],
            'phone'   => ['sometimes', 'string', 'max:20'],
            'email'   => ['nullable', 'email', 'max:150'],
            'crea'    => ['nullable', 'string', 'max:30'],
            'region'  => ['nullable', 'string', 'max:100'],
            'status'  => ['sometimes', 'string'],
            'active'  => ['sometimes', 'boolean'],
            'user_id' => ['nullable', 'uuid', 'exists:users,id'],
        ]);

        $technician->update($data);

        return response()->json($technician);
    }

    public function destroy(string $id): JsonResponse
    {
        Technician::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    /**
     * GET /api/technicians/available
     * Lista mecânicos disponíveis para despacho
     */
    public function available(Request $request): JsonResponse
    {
        $technicians = Technician::available()
            ->when($request->filled('region'), fn($q) => $q->byRegion($request->region))
            ->orderBy('name')
            ->get();

        return response()->json($technicians);
    }
}
