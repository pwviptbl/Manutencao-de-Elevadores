<?php

namespace App\Events;

use App\Models\ServiceOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceOrderUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ServiceOrder $order
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("tenant.{$this->order->tenant_id}"),
        ];

        // Notifica também o canal privado do mecânico
        if ($this->order->assigned_technician_id) {
            $channels[] = new PrivateChannel("technician.{$this->order->assigned_technician_id}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'service-order.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id'                      => $this->order->id,
            'status'                  => $this->order->status,
            'priority'                => $this->order->priority,
            'assigned_technician_id'  => $this->order->assigned_technician_id,
            'sla_violated_at'         => $this->order->sla_violated_at?->toIso8601String(),
            'updated_at'              => $this->order->updated_at->toIso8601String(),
        ];
    }
}
