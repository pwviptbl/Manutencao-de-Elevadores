<?php

namespace App\Http\Controllers\Public\V1;

use App\Http\Controllers\Controller;
use App\Models\Condominium;
use App\Models\Elevator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Pública v1 — Condomínios
 *
 * Endpoints:
 *   GET    /api/v1/condominiums                    → index()
 *   POST   /api/v1/condominiums                    → store()
 *   GET    /api/v1/condominiums/{id}               → show()
 *   PUT    /api/v1/condominiums/{id}               → update()
 *   DELETE /api/v1/condominiums/{id}               → destroy()
 *   GET    /api/v1/condominiums/{id}/elevators     → elevators()
 */
class CondominiumController extends Controller
{
    /**
     * GET /api/v1/condominiums
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search'   => ['nullable', 'string', 'max:100'],
            'city'     => ['nullable', 'string', 'max:100'],
            'state'    => ['nullable', 'string', 'size:2'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor'   => ['nullable', 'string'],
        ]);

        $query = Condominium::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('cnpj', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('city')) {
            $query->where('city', 'ILIKE', "%{$request->city}%");
        }

        if ($request->filled('state')) {
            $query->where('state', strtoupper($request->state));
        }

        $condominiums = $query
            ->orderBy('name')
            ->cursorPaginate($request->per_page ?? 25);

        return response()->json([
            'data' => $condominiums->items(),
            'meta' => [
                'per_page'    => $condominiums->perPage(),
                'next_cursor' => $condominiums->nextCursor()?->encode(),
                'prev_cursor' => $condominiums->previousCursor()?->encode(),
            ],
        ]);
    }

    /**
     * POST /api/v1/condominiums
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'cnpj'      => ['required', 'string', 'size:18'],
            'address'   => ['required', 'string', 'max:255'],
            'cep'       => ['required', 'string', 'size:9'],
            'city'      => ['required', 'string', 'max:100'],
            'state'     => ['required', 'string', 'size:2'],
            'phone'     => ['required', 'string', 'max:20'],
            'email'     => ['required', 'email', 'max:255'],
            'sla_hours' => ['nullable', 'integer', 'min:1'],
        ]);

        /** @var \App\Models\ApiKey $apiKey */
        $apiKey       = $request->attributes->get('api_key');
        $condominium  = Condominium::create([...$data, 'tenant_id' => $apiKey->tenant_id]);

        return response()->json(['data' => $condominium], 201);
    }

    /**
     * GET /api/v1/condominiums/{id}
     */
    public function show(string $id): JsonResponse
    {
        $condominium = Condominium::withCount('elevators')->findOrFail($id);

        return response()->json(['data' => $condominium]);
    }

    /**
     * PUT /api/v1/condominiums/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $condominium = Condominium::findOrFail($id);

        $data = $request->validate([
            'name'      => ['nullable', 'string', 'max:255'],
            'address'   => ['nullable', 'string', 'max:255'],
            'cep'       => ['nullable', 'string', 'size:9'],
            'city'      => ['nullable', 'string', 'max:100'],
            'state'     => ['nullable', 'string', 'size:2'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'email'     => ['nullable', 'email', 'max:255'],
            'sla_hours' => ['nullable', 'integer', 'min:1'],
        ]);

        $condominium->update($data);

        return response()->json(['data' => $condominium]);
    }

    /**
     * DELETE /api/v1/condominiums/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        Condominium::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    /**
     * GET /api/v1/condominiums/{id}/elevators
     * Lista os elevadores vinculados ao condomínio.
     */
    public function elevators(Request $request, string $id): JsonResponse
    {
        $condominium = Condominium::findOrFail($id);

        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor'   => ['nullable', 'string'],
        ]);

        $elevators = Elevator::where('condominium_id', $condominium->id)
            ->orderBy('serial_number')
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
}
