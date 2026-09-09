<?php

use Illuminate\Support\Facades\Schedule;

// Los anuncios caducados salen del catálogo cada madrugada.
Schedule::command('listings:expire')->dailyAt('03:00');
