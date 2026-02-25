<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Condominium;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CondominiumController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search'   => ['nullable', 'string', 'max:100'],
            'active'   => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Condominium::withCount('elevators');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('cnpj', 'like', "%{$search}%")
                  ->orWhere('city', 'ILIKE', "%{$search}%");
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
            'name'               => ['required', 'string', 'max:200'],
            'cnpj'               => ['nullable', 'string', 'max:18'],
            'address'            => ['required', 'string', 'max:255'],
            'address_number'     => ['nullable', 'string', 'max:20'],
            'address_complement' => ['nullable', 'string', 'max:100'],
            'neighborhood'       => ['nullable', 'string', 'max:100'],
            'city'               => ['required', 'string', 'max:100'],
            'state'              => ['required', 'string', 'size:2'],
            'zip_code'           => ['required', 'string', 'max:9'],
            'phone'              => ['nullable', 'string', 'max:20'],
            'email'              => ['nullable', 'email', 'max:150'],
            'contact_name'       => ['nullable', 'string', 'max:100'],
            'sla_hours'          => ['nullable', 'integer', 'min:1', 'max:720'],
            'notes'              => ['nullable', 'string', 'max:1000'],
        ]);

        $condominium = Condominium::create([
            ...$data,
            'tenant_id' => auth()->user()->tenant_id,
        ]);

        return response()->json($condominium, 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(
            Condominium::withCount('elevators')
                ->with('elevators:id,condominium_id,serial_number,manufacturer,model,active')
                ->findOrFail($id)
        );
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $condominium = Condominium::findOrFail($id);

        $data = $request->validate([
            'name'               => ['sometimes', 'string', 'max:200'],
            'cnpj'               => ['nullable', 'string', 'max:18'],
            'address'            => ['sometimes', 'string', 'max:255'],
            'address_number'     => ['nullable', 'string', 'max:20'],
            'address_complement' => ['nullable', 'string', 'max:100'],
            'neighborhood'       => ['nullable', 'string', 'max:100'],
            'city'               => ['sometimes', 'string', 'max:100'],
            'state'              => ['sometimes', 'string', 'size:2'],
            'zip_code'           => ['sometimes', 'string', 'max:9'],
            'phone'              => ['nullable', 'string', 'max:20'],
            'email'              => ['nullable', 'email', 'max:150'],
            'contact_name'       => ['nullable', 'string', 'max:100'],
            'sla_hours'          => ['nullable', 'integer', 'min:1', 'max:720'],
            'active'             => ['sometimes', 'boolean'],
            'notes'              => ['nullable', 'string', 'max:1000'],
        ]);

        $condominium->update($data);

        return response()->json($condominium);
    }

    public function destroy(string $id): JsonResponse
    {
        Condominium::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
