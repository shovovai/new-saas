<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The command itself decides which websites are actually due, based on
// each team's plan scan_frequency (5min up to daily) — running this every
// minute just means no site ever waits longer than necessary.
Schedule::command('monitoring:run')->everyMinute()->withoutOverlapping();
