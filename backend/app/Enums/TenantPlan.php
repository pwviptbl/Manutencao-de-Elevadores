<?php

namespace App\Enums;

enum TenantPlan: string
{
    case Starter    = 'starter';
    case Pro        = 'pro';
    case Business   = 'business';
    case Enterprise = 'enterprise';

    public function label(): string
    {
        return match($this) {
            self::Starter    => 'Starter',
            self::Pro        => 'Pro',
            self::Business   => 'Business',
            self::Enterprise => 'Enterprise',
        };
    }

    public function maxTechnicians(): ?int
    {
        return match($this) {
            self::Starter    => 5,
            self::Pro        => 15,
            self::Business   => null, // ilimitado
            self::Enterprise => null,
        };
    }

    public function maxOrdersPerMonth(): ?int
    {
        return match($this) {
            self::Starter    => 100,
            self::Pro        => null,
            self::Business   => null,
            self::Enterprise => null,
        };
    }

    public function hasAiWhatsApp(): bool
    {
        return in_array($this, [self::Pro, self::Business, self::Enterprise]);
    }

    public function hasAiVoice(): bool
    {
        return in_array($this, [self::Business, self::Enterprise]);
    }

    public function hasNfse(): bool
    {
        return in_array($this, [self::Pro, self::Business, self::Enterprise]);
    }

    public function hasStock(): bool
    {
        return in_array($this, [self::Business, self::Enterprise]);
    }
}
