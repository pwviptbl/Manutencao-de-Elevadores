<?php

namespace App\Models;

use App\Enums\TechnicianStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Technician extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'phone',
        'email',
        'crea',
        'region',
        'status',
        'metadata',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'status'   => TechnicianStatus::class,
            'metadata' => 'array',
            'active'   => 'boolean',
        ];
    }

    // ─────────────────────────────────────────
    // Relacionamentos
    // ─────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function serviceOrders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class, 'assigned_technician_id');
    }

    // ─────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────

    public function scopeAvailable($query)
    {
        return $query->where('status', TechnicianStatus::Available)->where('active', true);
    }

    public function scopeByRegion($query, string $region)
    {
        return $query->where('region', 'ILIKE', "%{$region}%");
    }

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────

    public function isAvailable(): bool
    {
        return $this->status === TechnicianStatus::Available && $this->active;
    }
}
