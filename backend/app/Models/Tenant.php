<?php

namespace App\Models;

use App\Enums\TenantPlan;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'plan',
        'cnpj',
        'email',
        'phone',
        'settings',
        'metadata',
        'active',
        'trial_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'plan'          => TenantPlan::class,
            'settings'      => 'array',
            'metadata'      => 'array',
            'active'        => 'boolean',
            'trial_ends_at' => 'datetime',
        ];
    }

    // ─────────────────────────────────────────
    // Relacionamentos
    // ─────────────────────────────────────────

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function condominiums(): HasMany
    {
        return $this->hasMany(Condominium::class);
    }

    public function elevators(): HasMany
    {
        return $this->hasMany(Elevator::class);
    }

    public function technicians(): HasMany
    {
        return $this->hasMany(Technician::class);
    }

    public function serviceOrders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class);
    }

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }
}
