<?php

namespace App\Enums;

enum ServiceOrderType: string
{
    case Corrective  = 'corrective';
    case Preventive  = 'preventive';
    case Emergency   = 'emergency';

    public function label(): string
    {
        return match($this) {
            self::Corrective => 'Corretiva',
            self::Preventive => 'Preventiva',
            self::Emergency  => 'Emergência',
        };
    }
}

enum ServiceOrderOrigin: string
{
    case Panel      = 'panel';
    case WhatsApp   = 'whatsapp';
    case Voice      = 'voice';
    case Import     = 'import';
    case Ai         = 'ai';

    public function label(): string
    {
        return match($this) {
            self::Panel    => 'Painel',
            self::WhatsApp => 'WhatsApp',
            self::Voice    => 'Voz',
            self::Import   => 'Importação',
            self::Ai       => 'IA',
        };
    }
}
