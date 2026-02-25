<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CondominiumController;
use App\Http\Controllers\Api\DispatchController;
use App\Http\Controllers\Api\ElevatorController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\ServiceOrderController;
use App\Http\Controllers\Api\TechnicianController;
use Illuminate\Support\Facades\Route;

/*
|─────────────────────────────────────────────────────────
| ROTAS PÚBLICAS (sem autenticação)
|─────────────────────────────────────────────────────────
*/
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

/*
|─────────────────────────────────────────────────────────
| ROTAS PROTEGIDAS (Sanctum + EnsureTenant)
|─────────────────────────────────────────────────────────
*/
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::get('me',          [AuthController::class, 'me']);
        Route::post('logout',     [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
    });

    // ─────────────────────────────────────────
    // ORDENS DE SERVIÇO
    // ─────────────────────────────────────────
    Route::prefix('service-orders')->group(function () {
        Route::get('/',          [ServiceOrderController::class, 'index']);
        Route::post('/',         [ServiceOrderController::class, 'store']);
        Route::get('/{id}',      [ServiceOrderController::class, 'show']);
        Route::put('/{id}',      [ServiceOrderController::class, 'update']);
        Route::patch('/{id}/status', [ServiceOrderController::class, 'updateStatus']);
        Route::delete('/{id}',   [ServiceOrderController::class, 'destroy'])
            ->middleware('role:admin');
    });

    // ─────────────────────────────────────────
    // CONDOMÍNIOS
    // ─────────────────────────────────────────
    Route::apiResource('condominiums', CondominiumController::class);

    // ─────────────────────────────────────────
    // ELEVADORES
    // ─────────────────────────────────────────
    Route::apiResource('elevators', ElevatorController::class);

    // ─────────────────────────────────────────
    // MECÂNICOS
    // ─────────────────────────────────────────
    Route::get('technicians/available', [TechnicianController::class, 'available']);
    Route::apiResource('technicians', TechnicianController::class);

    // ─────────────────────────────────────────
    // DESPACHO
    // ─────────────────────────────────────────
    Route::prefix('dispatch')->group(function () {
        Route::get('queue',                [DispatchController::class, 'queue']);
        Route::post('{orderId}/assign',    [DispatchController::class, 'assign']);
        Route::post('{orderId}/unassign',  [DispatchController::class, 'unassign']);
    });

    // ─────────────────────────────────────────
    // IMPORTAÇÕES (CSV / XLSX)
    // ─────────────────────────────────────────
    Route::prefix('imports')->group(function () {
        Route::get('/',                    [ImportController::class, 'index']);
        Route::post('/',                   [ImportController::class, 'store']);
        Route::get('/{id}',                [ImportController::class, 'show']);
        Route::get('/templates/{type}',    [ImportController::class, 'template']);
    });

    // ─────────────────────────────────────────
    // DASHBOARD (KPIs para o painel em tempo real)
    // ─────────────────────────────────────────
    Route::get('dashboard', function () {
        $tenantId = auth()->user()->tenant_id;

        return response()->json([
            'open_orders'      => \App\Models\ServiceOrder::active()->count(),
            'emergency_orders' => \App\Models\ServiceOrder::emergency()->active()->count(),
            'sla_violations'   => \App\Models\ServiceOrder::overdueSla()->count(),
            'technicians'      => [
                'available' => \App\Models\Technician::available()->count(),
                'on_call'   => \App\Models\Technician::where('status', 'on_call')->count(),
            ],
        ]);
    });

});
