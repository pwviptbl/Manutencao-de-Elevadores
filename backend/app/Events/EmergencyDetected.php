<?php

namespace App\Events;

use App\Models\ServiceOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento de emergência (P0): dispara alerta visual/sonoro no painel.
 * Este evento é emitido SEMPRE que um chamado P0 é criado,
 * independentemente de ser via IA, WhatsApp ou painel.
 */
class EmergencyDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ServiceOrder $order
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->order->tenant_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'emergency.detected';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id'       => $this->order->id,
            'title'          => $this->order->title,
            'description'    => $this->order->description,
            'caller_name'    => $this->order->caller_name,
            'caller_phone'   => $this->order->caller_phone,
            'condominium'    => [
                'id'      => $this->order->condominium?->id,
                'name'    => $this->order->condominium?->name,
                'address' => $this->order->condominium?->full_address,
            ],
            'elevator'       => [
                'id'            => $this->order->elevator?->id,
                'serial_number' => $this->order->elevator?->serial_number,
            ],
            'created_at'     => $this->order->created_at->toIso8601String(),
            // Som e cor de alerta são definidos no frontend
            'alert_sound'    => true,
            'alert_color'    => 'red',
        ];
    }
}
