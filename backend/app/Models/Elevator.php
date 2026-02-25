<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Elevator extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'condominium_id',
        'serial_number',
        'manufacturer',
        'model',
        'floor_count',
        'capacity',
        'type',
        'installation_date',
        'last_revision_date',
        'next_revision_date',
        'photos',
        'active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'floor_count'       => 'integer',
            'photos'            => 'array',
            'installation_date' => 'date',
            'last_revision_date'=> 'date',
            'next_revision_date'=> 'date',
            'active'            => 'boolean',
        ];
    }

    // ─────────────────────────────────────────
    // Relacionamentos
    // ─────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function condominium(): BelongsTo
    {
        return $this->belongsTo(Condominium::class);
    }

    public function serviceOrders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class);
    }

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────

    public function isRevisionOverdue(): bool
    {
        return $this->next_revision_date && $this->next_revision_date->isPast();
    }
}
