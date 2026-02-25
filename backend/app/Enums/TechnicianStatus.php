<?php

namespace App\Enums;

enum TechnicianStatus: string
{
    case Available   = 'available';
    case OnCall      = 'on_call';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match($this) {
            self::Available   => 'Disponível',
            self::OnCall      => 'Em Atendimento',
            self::Unavailable => 'Indisponível',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Available   => 'green',
            self::OnCall      => 'orange',
            self::Unavailable => 'gray',
        };
    }
}
