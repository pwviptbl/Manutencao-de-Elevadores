<?php

use Illuminate\Support\Facades\Schedule;

/*
|─────────────────────────────────────────────────────────
| Agendamentos do Laravel Scheduler
|─────────────────────────────────────────────────────────
*/

// Verifica e marca SLAs violados a cada 5 minutos
Schedule::command('orders:check-sla')->everyFiveMinutes();
