<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenant
{
    /**
     * Injeta o tenant_id da sessão no contexto do PostgreSQL para que
     * as políticas de RLS funcionem corretamente em todas as queries.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Rotas públicas (login, health) não precisam de tenant
        if (! auth()->check()) {
            return $next($request);
        }

        $tenantId = auth()->user()->tenant_id;

        if (! $tenantId) {
            return response()->json([
                'message' => 'Tenant não identificado.',
            ], 403);
        }

        // Verifica se o tenant está ativo
        $tenant = auth()->user()->tenant;

        if (! $tenant || ! $tenant->active) {
            return response()->json([
                'message' => 'Tenant inativo ou não encontrado.',
            ], 403);
        }

        // Injeta o tenant_id no contexto da sessão PostgreSQL
        // Isso ativa as políticas de RLS para todas as queries da requisição
        DB::statement(
            "SELECT set_config('app.tenant_id', ?, false)",
            [(string) $tenantId]
        );

        // Disponibiliza o tenant no request para uso nos controllers
        $request->merge(['_tenant' => $tenant]);

        return $next($request);
    }
}
