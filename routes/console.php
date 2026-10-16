<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('nomowly:health', function () {
    $this->info('=== Nomowly Platform Health Check ===');
    $this->line('Laravel Version: ' . app()->version());
    $this->line('PHP Version: ' . PHP_VERSION);
    $this->line('Environment: ' . app()->environment());
    $this->line('Default DB Connection: ' . config('database.default'));
    $this->line('Queue Connection: ' . config('queue.default'));
    $this->line('Default Currency: ' . config('nomowly.default_currency'));
    $this->line('Supported Currencies: ' . implode(', ', array_keys(config('nomowly.currencies'))));
    $this->info('Hostinger Shared Hosting Compatibility: Ready');
})->purpose('Display Nomowly platform status and configuration');

// Laravel Scheduler Registration for Hostinger Shared Hosting Cron
// Hostinger Cron runs: * * * * * php artisan schedule:run >> /dev/null 2>&1

// 1. Process database queue on shared hosting (process pending jobs and stop when empty)
Schedule::command('queue:work database --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();

// 2. Prune old failed jobs and batches weekly
Schedule::command('queue:prune-failed --hours=168')
    ->weekly();

Schedule::command('queue:prune-batches --hours=168')
    ->weekly();
