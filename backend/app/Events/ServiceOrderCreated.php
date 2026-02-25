<?php

namespace App\Events;

use App\Models\ServiceOrder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceOrderCreated implements ShouldBroadcast
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
        return 'service-order.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id'            => $this->order->id,
            'title'         => $this->order->title,
            'priority'      => $this->order->priority,
            'status'        => $this->order->status,
            'type'          => $this->order->type,
            'origin'        => $this->order->origin,
            'sla_deadline'  => $this->order->sla_deadline?->toIso8601String(),
            'condominium'   => [
                'id'   => $this->order->condominium?->id,
                'name' => $this->order->condominium?->name,
            ],
            'elevator'      => [
                'id'            => $this->order->elevator?->id,
                'serial_number' => $this->order->elevator?->serial_number,
            ],
            'is_emergency'  => $this->order->isEmergency(),
            'created_at'    => $this->order->created_at->toIso8601String(),
        ];
    }
}
