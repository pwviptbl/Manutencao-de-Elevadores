<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: api.rate-limit
 *
 * Aplica rate limiting por API Key conforme o plano do tenant.
 *
 * Limites padrão por plano:
 *   Starter    →  60 req/min
 *   Pro        → 120 req/min
 *   Business   → 300 req/min
 *   Enterprise → configurável (sem limite padrão)
 *
 * Headers retornados:
 *   X-RateLimit-Limit     → limite total da key
 *   X-RateLimit-Remaining → requisições restantes na janela atual
 *   X-RateLimit-Reset     → timestamp Unix do reset
 *
 * TODO: implementar após criar model ApiKey e configurar Redis.
 */
class ApiRateLimiter
{
    public function handle(Request $request, Closure $next): Response
    {
        // TODO: implementar após criar model ApiKey
        //
        // /** @var \App\Models\ApiKey $apiKey */
        // $apiKey = $request->attributes->get('api_key');
        //
        // $key     = "rate_limit:{$apiKey->id}:" . now()->format('Y-m-d-H-i');
        // $limit   = $apiKey->rate_limit;
        // $current = \Cache::increment($key);
        //
        // if ($current === 1) {
        //     \Cache::expire($key, 60);
        // }
        //
        // $remaining = max(0, $limit - $current);
        // $resetAt   = now()->addMinute()->startOfMinute();
        //
        // $request->attributes->set('rate_limit_remaining', $remaining);
        // $request->attributes->set('rate_limit_reset_at', $resetAt->toIso8601String());
        //
        // if ($current > $limit) {
        //     return response()->json([
        //         'error' => [
        //             'code'    => 'RATE_LIMIT_EXCEEDED',
        //             'message' => 'Limite de requisições excedido. Tente novamente em breve.',
        //             'reset_at' => $resetAt->toIso8601String(),
        //         ],
        //     ], 429)->withHeaders([
        //         'X-RateLimit-Limit'     => $limit,
        //         'X-RateLimit-Remaining' => 0,
        //         'X-RateLimit-Reset'     => $resetAt->timestamp,
        //         'Retry-After'           => $resetAt->diffInSeconds(now()),
        //     ]);
        // }
        //
        // $response = $next($request);
        //
        // return $response->withHeaders([
        //     'X-RateLimit-Limit'     => $limit,
        //     'X-RateLimit-Remaining' => $remaining,
        //     'X-RateLimit-Reset'     => $resetAt->timestamp,
        // ]);

        return $next($request);
    }
}
