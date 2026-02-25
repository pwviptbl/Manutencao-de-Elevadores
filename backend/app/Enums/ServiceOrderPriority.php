<?php

namespace App\Enums;

enum ServiceOrderPriority: string
{
    case P0 = 'P0'; // Emergência
    case P1 = 'P1'; // Urgente
    case P2 = 'P2'; // Normal
    case P3 = 'P3'; // Baixa

    public function label(): string
    {
        return match($this) {
            self::P0 => 'Emergência',
            self::P1 => 'Urgente',
            self::P2 => 'Normal',
            self::P3 => 'Baixa',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::P0 => 'red',
            self::P1 => 'orange',
            self::P2 => 'blue',
            self::P3 => 'gray',
        };
    }

    /** SLA em horas para cada prioridade */
    public function slaHours(): int
    {
        return match($this) {
            self::P0 => 1,
            self::P1 => 4,
            self::P2 => 24,
            self::P3 => 72,
        };
    }

    public function isEmergency(): bool
    {
        return $this === self::P0;
    }

    public function sortWeight(): int
    {
        return match($this) {
            self::P0 => 4,
            self::P1 => 3,
            self::P2 => 2,
            self::P3 => 1,
        };
    }
}
