<?php

namespace App\Http\Controllers\Public\V1;

use App\Http\Controllers\Controller;
use App\Models\Elevator;
use App\Models\ServiceOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Pública v1 — Elevadores
 *
 * Endpoints:
 *   GET    /api/v1/elevators                → index()
 *   POST   /api/v1/elevators                → store()
 *   GET    /api/v1/elevators/{id}           → show()
 *   PUT    /api/v1/elevators/{id}           → update()
 *   DELETE /api/v1/elevators/{id}           → destroy()
 *   GET    /api/v1/elevators/{id}/orders    → orders()
 */
class ElevatorController extends Controller
{
    /**
     * GET /api/v1/elevators
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'condominium_id' => ['nullable', 'uuid'],
            'search'         => ['nullable', 'string', 'max:100'],
            'per_page'       => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor'         => ['nullable', 'string'],
        ]);

        $query = Elevator::with('condominium:id,name,city,state');

        if ($request->filled('condominium_id')) {
            $query->where('condominium_id', $request->condominium_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('serial_number', 'ILIKE', "%{$search}%")
                  ->orWhere('manufacturer', 'ILIKE', "%{$search}%")
                  ->orWhere('model', 'ILIKE', "%{$search}%");
            });
        }

        $elevators = $query
            ->orderBy('created_at', 'desc')
            ->cursorPaginate($request->per_page ?? 25);

        return response()->json([
            'data' => $elevators->items(),
            'meta' => [
                'per_page'    => $elevators->perPage(),
                'next_cursor' => $elevators->nextCursor()?->encode(),
                'prev_cursor' => $elevators->previousCursor()?->encode(),
            ],
        ]);
    }

    /**
     * POST /api/v1/elevators
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'condominium_id'     => ['required', 'uuid', 'exists:condominiums,id'],
            'serial_number'      => ['required', 'string', 'max:100'],
            'manufacturer'       => ['required', 'string', 'max:100'],
            'model'              => ['required', 'string', 'max:100'],
            'floor'              => ['required', 'integer', 'min:1'],
            'last_revision_date' => ['nullable', 'date'],
            'notes'              => ['nullable', 'string', 'max:2000'],
        ]);

        /** @var \App\Models\ApiKey $apiKey */
        $apiKey   = $request->attributes->get('api_key');
        $elevator = Elevator::create([...$data, 'tenant_id' => $apiKey->tenant_id]);

        return response()->json(['data' => $elevator], 201);
    }

    /**
     * GET /api/v1/elevators/{id}
     */
    public function show(string $id): JsonResponse
    {
        $elevator = Elevator::with('condominium:id,name,city,state,cnpj')->findOrFail($id);

        return response()->json(['data' => $elevator]);
    }

    /**
     * PUT /api/v1/elevators/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $elevator = Elevator::findOrFail($id);

        $data = $request->validate([
            'serial_number'      => ['nullable', 'string', 'max:100'],
            'manufacturer'       => ['nullable', 'string', 'max:100'],
            'model'              => ['nullable', 'string', 'max:100'],
            'floor'              => ['nullable', 'integer', 'min:1'],
            'last_revision_date' => ['nullable', 'date'],
            'notes'              => ['nullable', 'string', 'max:2000'],
        ]);

        $elevator->update($data);

        return response()->json(['data' => $elevator]);
    }

    /**
     * DELETE /api/v1/elevators/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        Elevator::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    /**
     * GET /api/v1/elevators/{id}/orders
     * Histórico de OS do elevador.
     */
    public function orders(Request $request, string $id): JsonResponse
    {
        $elevator = Elevator::findOrFail($id);

        $request->validate([
            'status'   => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor'   => ['nullable', 'string'],
        ]);

        $query = ServiceOrder::where('elevator_id', $elevator->id)
            ->with('assignedTechnician:id,name');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query
            ->orderBy('created_at', 'desc')
            ->cursorPaginate($request->per_page ?? 25);

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'per_page'    => $orders->perPage(),
                'next_cursor' => $orders->nextCursor()?->encode(),
                'prev_cursor' => $orders->previousCursor()?->encode(),
            ],
        ]);
    }
}
