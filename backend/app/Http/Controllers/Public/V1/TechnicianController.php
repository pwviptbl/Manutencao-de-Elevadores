<?php

namespace App\Http\Controllers\Public\V1;

use App\Http\Controllers\Controller;
use App\Models\Technician;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Pública v1 — Técnicos / Mecânicos
 *
 * Endpoints:
 *   GET  /api/v1/technicians        → index()
 *   GET  /api/v1/technicians/{id}   → show()
 *
 * Nota: A API pública não permite criar/editar/remover técnicos —
 * essa gestão é feita exclusivamente pelo painel interno.
 */
class TechnicianController extends Controller
{
    /**
     * GET /api/v1/technicians
     * Lista técnicos com filtro por disponibilidade e região.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'available' => ['nullable', 'boolean'],
            'region'    => ['nullable', 'string', 'max:100'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor'    => ['nullable', 'string'],
        ]);

        $query = Technician::query();

        if ($request->filled('available')) {
            $query->where('is_available', filter_var($request->available, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('region')) {
            $query->where('region', 'ILIKE', "%{$request->region}%");
        }

        $technicians = $query
            ->orderBy('name')
            ->cursorPaginate($request->per_page ?? 25);

        // Remove informações sensíveis da resposta pública
        $items = collect($technicians->items())->map(fn ($t) => [
            'id'           => $t->id,
            'name'         => $t->name,
            'region'       => $t->region,
            'is_available' => $t->is_available,
            'status'       => $t->status,
        ]);

        return response()->json([
            'data' => $items,
            'meta' => [
                'per_page'    => $technicians->perPage(),
                'next_cursor' => $technicians->nextCursor()?->encode(),
                'prev_cursor' => $technicians->previousCursor()?->encode(),
            ],
        ]);
    }

    /**
     * GET /api/v1/technicians/{id}
     */
    public function show(string $id): JsonResponse
    {
        $t = Technician::findOrFail($id);

        return response()->json([
            'data' => [
                'id'           => $t->id,
                'name'         => $t->name,
                'region'       => $t->region,
                'is_available' => $t->is_available,
                'status'       => $t->status,
            ],
        ]);
    }
}
