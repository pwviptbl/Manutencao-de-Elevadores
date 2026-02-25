<?php

namespace App\Models;

use App\Enums\ServiceOrderOrigin;
use App\Enums\ServiceOrderPriority;
use App\Enums\ServiceOrderStatus;
use App\Enums\ServiceOrderType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ServiceOrder extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    protected $table = 'service_orders';

    protected $fillable = [
        'tenant_id',
        'elevator_id',
        'condominium_id',
        'assigned_technician_id',
        'created_by_user_id',
        'priority',
        'status',
        'type',
        'origin',
        'title',
        'description',
        'resolution_notes',
        'photos',
        'checklist',
        'signature_url',
        'sla_deadline',
        'sla_violated_at',
        'assigned_at',
        'started_at',
        'completed_at',
        'closed_at',
        'caller_name',
        'caller_phone',
        'ai_metadata',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'priority'       => ServiceOrderPriority::class,
            'status'         => ServiceOrderStatus::class,
            'type'           => ServiceOrderType::class,
            'origin'         => ServiceOrderOrigin::class,
            'photos'         => 'array',
            'checklist'      => 'array',
            'ai_metadata'    => 'array',
            'metadata'       => 'array',
            'sla_deadline'   => 'datetime',
            'sla_violated_at'=> 'datetime',
            'assigned_at'    => 'datetime',
            'started_at'     => 'datetime',
            'completed_at'   => 'datetime',
            'closed_at'      => 'datetime',
        ];
    }

    // ─────────────────────────────────────────
    // Activity Log
    // ─────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('service_orders');
    }

    // ─────────────────────────────────────────
    // Relacionamentos
    // ─────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function elevator(): BelongsTo
    {
        return $this->belongsTo(Elevator::class);
    }

    public function condominium(): BelongsTo
    {
        return $this->belongsTo(Condominium::class);
    }

    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(Technician::class, 'assigned_technician_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // ─────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            ServiceOrderStatus::Open,
            ServiceOrderStatus::Assigned,
            ServiceOrderStatus::InProgress,
        ]);
    }

    public function scopeEmergency($query)
    {
        return $query->where('priority', ServiceOrderPriority::P0);
    }

    public function scopeOverdueSla($query)
    {
        return $query->whereNotNull('sla_deadline')
                     ->where('sla_deadline', '<', now())
                     ->whereNull('sla_violated_at')
                     ->active();
    }

    public function scopeForTechnician($query, string $technicianId)
    {
        return $query->where('assigned_technician_id', $technicianId);
    }

    // ─────────────────────────────────────────
    // Máquina de estados
    // ─────────────────────────────────────────

    public function canTransitionTo(ServiceOrderStatus $next): bool
    {
        return $this->status->canTransitionTo($next);
    }

    /**
     * Transiciona para o próximo status, registrando timestamps.
     */
    public function transitionTo(ServiceOrderStatus $next, ?User $by = null): static
    {
        if (! $this->canTransitionTo($next)) {
            throw new \DomainException(
                "Transição inválida: {$this->status->value} → {$next->value}"
            );
        }

        $timestamps = match ($next) {
            ServiceOrderStatus::Assigned   => ['assigned_at' => now()],
            ServiceOrderStatus::InProgress => ['started_at' => now()],
            ServiceOrderStatus::Completed  => ['completed_at' => now()],
            ServiceOrderStatus::Closed     => ['closed_at' => now()],
            default                        => [],
        };

        $this->update(['status' => $next, ...$timestamps]);

        activity()
            ->causedBy($by)
            ->performedOn($this)
            ->withProperties(['from' => $this->getOriginal('status'), 'to' => $next])
            ->log("Status alterado para {$next->label()}");

        return $this;
    }

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────

    public function isEmergency(): bool
    {
        return $this->priority === ServiceOrderPriority::P0;
    }

    public function isSlaViolated(): bool
    {
        return $this->sla_deadline && $this->sla_deadline->isPast() && $this->status->isActive();
    }

    public function calculateSlaDeadline(): \Carbon\Carbon
    {
        return now()->addHours($this->priority->slaHours());
    }
}
