<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: api.key
 *
 * Autentica requisições à API Pública v1 via API Key.
 *
 * Fluxo:
 *   1. Extrai Bearer token do header Authorization
 *   2. Faz hash SHA-256 da key e busca em api_keys
 *   3. Valida: ativa, não expirada, tenant ativo
 *   4. Injeta tenant_id no contexto do RLS (PostgreSQL)
 *   5. Armazena ApiKey no request->attributes para uso pelos controllers
 *
 * TODO: implementar após criar model ApiKey e migration correspondente.
 */
class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'error' => [
                    'code'    => 'MISSING_API_KEY',
                    'message' => 'Authorization header com Bearer token é obrigatório.',
                ],
            ], 401);
        }

        // TODO: implementar após criar model ApiKey
        //
        // $keyHash = hash('sha256', $token);
        // $apiKey  = \App\Models\ApiKey::where('key_hash', $keyHash)
        //     ->where('is_active', true)
        //     ->whereNull('revoked_at')
        //     ->where(function ($q) {
        //         $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        //     })
        //     ->with('tenant')
        //     ->first();
        //
        // if (! $apiKey) {
        //     return response()->json([
        //         'error' => ['code' => 'INVALID_API_KEY', 'message' => 'API Key inválida ou revogada.'],
        //     ], 401);
        // }
        //
        // // Injetar tenant_id no RLS do PostgreSQL
        // \DB::statement("SET app.tenant_id = '{$apiKey->tenant_id}'");
        //
        // $apiKey->update(['last_used_at' => now()]);
        // $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }
}
