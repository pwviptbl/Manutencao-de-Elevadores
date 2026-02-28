<?php

namespace App\Http\Controllers\Public\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/auth/me
 *
 * Verifica a API Key autenticada e retorna informações do tenant,
 * scopes concedidos e estado atual do rate limit.
 */
class AuthMeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var \App\Models\ApiKey $apiKey */
        $apiKey = $request->attributes->get('api_key');

        return response()->json([
            'data' => [
                'tenant_id'   => $apiKey->tenant_id,
                'tenant_name' => $apiKey->tenant->name,
                'key_name'    => $apiKey->name,
                'key_prefix'  => $apiKey->key_prefix,
                'scopes'      => $apiKey->scopes,
                'rate_limit'  => [
                    'limit'     => $apiKey->rate_limit,
                    'remaining' => $request->attributes->get('rate_limit_remaining'),
                    'reset_at'  => $request->attributes->get('rate_limit_reset_at'),
                ],
            ],
        ]);
    }
}
