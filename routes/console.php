<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Deliver scheduled broadcasts every minute (App\Console\Commands\RunBroadcastCron).
// Laravel 12 reads the schedule from here, NOT from app/Console/Kernel.php.
Schedule::command('broadcast:run')->everyMinute();
