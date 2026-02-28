<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: api.scope
 *
 * Verifica se a API Key possui o(s) scope(s) necessário(s) para acessar
 * o endpoint.
 *
 * Scopes disponíveis:
 *   orders:read, orders:write
 *   elevators:read, elevators:write
 *   condominiums:read, condominiums:write
 *   technicians:read
 *   webhooks:manage
 *
 * Uso nas rotas:
 *   Route::middleware('api.scope:orders:write')
 *   Route::middleware('api.scope:orders:read,elevators:read')  // múltiplos (AND)
 *
 * TODO: implementar após criar model ApiKey.
 */
class CheckApiScope
{
    public function handle(Request $request, Closure $next, string ...$requiredScopes): Response
    {
        // TODO: implementar após criar model ApiKey
        //
        // /** @var \App\Models\ApiKey $apiKey */
        // $apiKey = $request->attributes->get('api_key');
        //
        // foreach ($requiredScopes as $scope) {
        //     if (! in_array($scope, $apiKey->scopes, true)) {
        //         return response()->json([
        //             'error' => [
        //                 'code'    => 'INSUFFICIENT_SCOPE',
        //                 'message' => "Esta API Key não possui o escopo '{$scope}'.",
        //                 'required_scope' => $scope,
        //             ],
        //         ], 403);
        //     }
        // }

        return $next($request);
    }
}
