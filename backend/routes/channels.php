<?php

use Illuminate\Support\Facades\Broadcast;

/*
|─────────────────────────────────────────────────────────
| Canais de Broadcasting — Laravel Reverb
|─────────────────────────────────────────────────────────
*/

/**
 * Canal privado por tenant: recebe todos os eventos do tenant.
 * Atendentes e admins se inscrevem aqui.
 */
Broadcast::channel('tenant.{tenantId}', function ($user, $tenantId) {
    return (string) $user->tenant_id === (string) $tenantId;
});

/**
 * Canal privado do mecânico: recebe chamados atribuídos a ele.
 */
Broadcast::channel('technician.{technicianId}', function ($user, $technicianId) {
    return $user->technician?->id === $technicianId;
});

/**
 * Canal de presença: quem está online no painel.
 */
Broadcast::channel('presence.tenant.{tenantId}', function ($user, $tenantId) {
    if ((string) $user->tenant_id !== (string) $tenantId) {
        return false;
    }

    return [
        'id'   => $user->id,
        'name' => $user->name,
        'role' => $user->getRoleNames()->first(),
    ];
});
