<?php

use App\Console\Commands\SyncInstrumentsFromTwelveData;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('instruments:sync-twelvedata')->dailyAt('06:00');
Schedule::command('instruments:refresh-prices')->everyTenMinutes()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
