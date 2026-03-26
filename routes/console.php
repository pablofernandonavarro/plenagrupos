<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Generate sessions for recurring groups 1 day in advance, every day at 08:00 Argentina time
Schedule::command('sessions:generate-recurring')
    ->dailyAt('08:00')
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping();

// Auto-close open attendances once the session time window has ended
Schedule::command('attendances:auto-close')
    ->everyFifteenMinutes()
    ->timezone('America/Argentina/Buenos_Aires')
    ->withoutOverlapping();

