<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Elevator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ElevatorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'condominium_id' => ['nullable', 'uuid'],
            'search'         => ['nullable', 'string', 'max:100'],
            'active'         => ['nullable', 'boolean'],
            'per_page'       => ['nullable', 'integer', 'min:1', 'max:100'],
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

        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        return response()->json(
            $query->orderBy('serial_number')->paginate($request->per_page ?? 25)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'condominium_id'     => ['required', 'uuid', 'exists:condominiums,id'],
            'serial_number'      => ['required', 'string', 'max:100'],
            'manufacturer'       => ['required', 'string', 'max:100'],
            'model'              => ['nullable', 'string', 'max:100'],
            'floor_count'        => ['nullable', 'integer', 'min:1'],
            'capacity'           => ['nullable', 'string', 'max:50'],
            'type'               => ['nullable', 'string', 'in:traction,hydraulic,pneumatic'],
            'installation_date'  => ['nullable', 'date'],
            'last_revision_date' => ['nullable', 'date'],
            'next_revision_date' => ['nullable', 'date'],
            'photos'             => ['nullable', 'array'],
            'notes'              => ['nullable', 'string', 'max:1000'],
        ]);

        $elevator = Elevator::create([
            ...$data,
            'tenant_id' => auth()->user()->tenant_id,
        ]);

        return response()->json($elevator->load('condominium:id,name'), 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(
            Elevator::with([
                'condominium:id,name,city,state',
                'serviceOrders' => fn($q) => $q->latest()->limit(10),
            ])->findOrFail($id)
        );
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $elevator = Elevator::findOrFail($id);

        $data = $request->validate([
            'serial_number'      => ['sometimes', 'string', 'max:100'],
            'manufacturer'       => ['sometimes', 'string', 'max:100'],
            'model'              => ['nullable', 'string', 'max:100'],
            'floor_count'        => ['nullable', 'integer', 'min:1'],
            'capacity'           => ['nullable', 'string', 'max:50'],
            'type'               => ['nullable', 'string', 'in:traction,hydraulic,pneumatic'],
            'installation_date'  => ['nullable', 'date'],
            'last_revision_date' => ['nullable', 'date'],
            'next_revision_date' => ['nullable', 'date'],
            'photos'             => ['nullable', 'array'],
            'active'             => ['sometimes', 'boolean'],
            'notes'              => ['nullable', 'string', 'max:1000'],
        ]);

        $elevator->update($data);

        return response()->json($elevator);
    }

    public function destroy(string $id): JsonResponse
    {
        Elevator::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
