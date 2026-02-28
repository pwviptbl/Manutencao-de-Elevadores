<?php

namespace App\Http\Controllers\Public\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * API Pública v1 — Webhooks
 *
 * Endpoints:
 *   GET    /api/v1/webhooks                        → index()
 *   POST   /api/v1/webhooks                        → store()
 *   GET    /api/v1/webhooks/{id}                   → show()
 *   DELETE /api/v1/webhooks/{id}                   → destroy()
 *   GET    /api/v1/webhooks/{id}/deliveries        → deliveries()
 *
 * Eventos disponíveis:
 *   order.created, order.status_changed, order.assigned,
 *   order.completed, order.closed, order.sla_warning,
 *   order.sla_violated, technician.availability_changed
 */
class WebhookController extends Controller
{
    private const ALLOWED_EVENTS = [
        'order.created',
        'order.status_changed',
        'order.assigned',
        'order.completed',
        'order.closed',
        'order.sla_warning',
        'order.sla_violated',
        'technician.availability_changed',
    ];

    /**
     * GET /api/v1/webhooks
     * Lista todos os webhooks registrados pelo tenant.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\ApiKey $apiKey */
        $apiKey = $request->attributes->get('api_key');

        // TODO: implementar model Webhook quando migration for criada
        // $webhooks = \App\Models\Webhook::where('tenant_id', $apiKey->tenant_id)
        //     ->orderBy('created_at', 'desc')
        //     ->get();

        return response()->json(['data' => []]);
    }

    /**
     * POST /api/v1/webhooks
     * Registra um novo webhook endpoint.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url'    => ['required', 'url', 'starts_with:https://'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['required', 'string', 'in:' . implode(',', self::ALLOWED_EVENTS)],
        ]);

        /** @var \App\Models\ApiKey $apiKey */
        $apiKey = $request->attributes->get('api_key');

        // TODO: implementar model Webhook quando migration for criada
        // $webhook = \App\Models\Webhook::create([
        //     'tenant_id'  => $apiKey->tenant_id,
        //     'api_key_id' => $apiKey->id,
        //     'url'        => $data['url'],
        //     'events'     => $data['events'],
        //     'secret'     => Str::random(32),
        //     'is_active'  => true,
        // ]);

        return response()->json([
            'data' => [
                'id'        => Str::uuid(),
                'url'       => $data['url'],
                'events'    => $data['events'],
                'is_active' => true,
                'secret'    => '*** implementar após migration ***',
            ],
        ], 201);
    }

    /**
     * GET /api/v1/webhooks/{id}
     */
    public function show(string $id): JsonResponse
    {
        // TODO: implementar model Webhook
        return response()->json(['data' => null], 404);
    }

    /**
     * DELETE /api/v1/webhooks/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        // TODO: implementar model Webhook
        return response()->json(null, 204);
    }

    /**
     * GET /api/v1/webhooks/{id}/deliveries
     * Log de tentativas de entrega do webhook.
     */
    public function deliveries(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status'   => ['nullable', 'string', 'in:success,failed,pending'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor'   => ['nullable', 'string'],
        ]);

        // TODO: implementar model WebhookDelivery
        return response()->json([
            'data' => [],
            'meta' => ['per_page' => 25, 'next_cursor' => null, 'prev_cursor' => null],
        ]);
    }
}
