<?php

namespace App\Events;

use App\Models\ImportJob;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ImportJob $importJob
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->importJob->tenant_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'import.progress';
    }

    public function broadcastWith(): array
    {
        $total     = $this->importJob->total_rows;
        $processed = $this->importJob->processed_rows;
        $percent   = $total > 0 ? round(($processed / $total) * 100) : 0;

        return [
            'import_id'      => $this->importJob->id,
            'type'           => $this->importJob->type,
            'status'         => $this->importJob->status,
            'total_rows'     => $total,
            'processed_rows' => $processed,
            'error_rows'     => $this->importJob->error_rows,
            'percent'        => $percent,
            'errors'         => $this->importJob->errors,
            'finished_at'    => $this->importJob->finished_at?->toIso8601String(),
        ];
    }
}
