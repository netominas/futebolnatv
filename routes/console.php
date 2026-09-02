<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (config('services.wosti.sync_enabled')) {
    Schedule::command('wosti:sync-events')
        ->cron(config('services.wosti.sync_cron'))
        ->timezone('America/Sao_Paulo')
        ->withoutOverlapping(20)
        ->onOneServer();

    Schedule::command('wosti:sync-logos')
        ->dailyAt('04:30')
        ->timezone('America/Sao_Paulo')
        ->withoutOverlapping(30)
        ->onOneServer();
}
