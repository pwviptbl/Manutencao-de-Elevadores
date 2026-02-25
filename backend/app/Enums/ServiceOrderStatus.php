<?php

namespace App\Enums;

enum ServiceOrderStatus: string
{
    case Open        = 'open';
    case Assigned    = 'assigned';
    case InProgress  = 'in_progress';
    case Completed   = 'completed';
    case Closed      = 'closed';
    case Cancelled   = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Open       => 'Aberto',
            self::Assigned   => 'Atribuído',
            self::InProgress => 'Em Andamento',
            self::Completed  => 'Concluído',
            self::Closed     => 'Fechado',
            self::Cancelled  => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Open       => 'blue',
            self::Assigned   => 'orange',
            self::InProgress => 'yellow',
            self::Completed  => 'green',
            self::Closed     => 'gray',
            self::Cancelled  => 'red',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Open, self::Assigned, self::InProgress]);
    }

    /** Transições válidas a partir deste status */
    public function allowedTransitions(): array
    {
        return match($this) {
            self::Open       => [self::Assigned, self::Cancelled],
            self::Assigned   => [self::InProgress, self::Open, self::Cancelled],
            self::InProgress => [self::Completed, self::Assigned],
            self::Completed  => [self::Closed],
            self::Closed     => [],
            self::Cancelled  => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions());
    }
}
