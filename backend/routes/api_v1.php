<?php

use App\Http\Controllers\Public\V1\AuthMeController;
use App\Http\Controllers\Public\V1\CondominiumController;
use App\Http\Controllers\Public\V1\ElevatorController;
use App\Http\Controllers\Public\V1\OrderController;
use App\Http\Controllers\Public\V1\TechnicianController;
use App\Http\Controllers\Public\V1\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|─────────────────────────────────────────────────────────────────────────────
| API PÚBLICA v1 — Autenticação por API Key
|─────────────────────────────────────────────────────────────────────────────
|
| Todas as rotas aqui exigem:
|   - Header: Authorization: Bearer elev_pk_...
|   - Middleware: api.key  → valida e injeta tenant_id no RLS
|   - Middleware: api.scope → verifica permissão por scope da key
|   - Middleware: api.rate-limit → aplica rate limit por plano do tenant
|
| Base URL: /api/v1/
|
*/

Route::middleware(['api.key', 'api.rate-limit'])->group(function () {

    // ─────────────────────────────────────────
    // VERIFICAÇÃO DE AUTENTICAÇÃO
    // ─────────────────────────────────────────

    /**
     * GET /api/v1/auth/me
     * Verifica a API Key e retorna info do tenant + scopes + rate limit atual.
     */
    Route::get('auth/me', [AuthMeController::class, 'show']);

    // ─────────────────────────────────────────
    // CHAMADOS / ORDENS DE SERVIÇO
    // ─────────────────────────────────────────

    Route::middleware('api.scope:orders:read')->group(function () {
        Route::get('orders',       [OrderController::class, 'index']);
        Route::get('orders/{id}',  [OrderController::class, 'show']);
    });

    Route::middleware('api.scope:orders:write')->group(function () {
        Route::post('orders',              [OrderController::class, 'store']);
        Route::put('orders/{id}',          [OrderController::class, 'update']);
        Route::patch('orders/{id}/status', [OrderController::class, 'updateStatus']);
        Route::delete('orders/{id}',       [OrderController::class, 'destroy']);
    });

    // ─────────────────────────────────────────
    // ELEVADORES
    // ─────────────────────────────────────────

    Route::middleware('api.scope:elevators:read')->group(function () {
        Route::get('elevators',             [ElevatorController::class, 'index']);
        Route::get('elevators/{id}',        [ElevatorController::class, 'show']);
        Route::get('elevators/{id}/orders', [ElevatorController::class, 'orders']);
    });

    Route::middleware('api.scope:elevators:write')->group(function () {
        Route::post('elevators',      [ElevatorController::class, 'store']);
        Route::put('elevators/{id}',  [ElevatorController::class, 'update']);
        Route::delete('elevators/{id}', [ElevatorController::class, 'destroy']);
    });

    // ─────────────────────────────────────────
    // CONDOMÍNIOS
    // ─────────────────────────────────────────

    Route::middleware('api.scope:condominiums:read')->group(function () {
        Route::get('condominiums',                  [CondominiumController::class, 'index']);
        Route::get('condominiums/{id}',             [CondominiumController::class, 'show']);
        Route::get('condominiums/{id}/elevators',   [CondominiumController::class, 'elevators']);
    });

    Route::middleware('api.scope:condominiums:write')->group(function () {
        Route::post('condominiums',       [CondominiumController::class, 'store']);
        Route::put('condominiums/{id}',   [CondominiumController::class, 'update']);
        Route::delete('condominiums/{id}', [CondominiumController::class, 'destroy']);
    });

    // ─────────────────────────────────────────
    // TÉCNICOS / MECÂNICOS
    // ─────────────────────────────────────────

    Route::middleware('api.scope:technicians:read')->group(function () {
        Route::get('technicians',        [TechnicianController::class, 'index']);
        Route::get('technicians/{id}',   [TechnicianController::class, 'show']);
    });

    // ─────────────────────────────────────────
    // WEBHOOKS
    // ─────────────────────────────────────────

    Route::middleware('api.scope:webhooks:manage')->group(function () {
        Route::get('webhooks',          [WebhookController::class, 'index']);
        Route::post('webhooks',         [WebhookController::class, 'store']);
        Route::get('webhooks/{id}',     [WebhookController::class, 'show']);
        Route::delete('webhooks/{id}',  [WebhookController::class, 'destroy']);

        // Log de deliveries de um webhook
        Route::get('webhooks/{id}/deliveries', [WebhookController::class, 'deliveries']);
    });
});
