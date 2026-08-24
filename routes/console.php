<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:seed-dummy', function () {
    $this->info('Starting dummy testing data seeder...');
    $this->call('db:seed', ['--class' => 'DummyDataSeeder']);
    $this->info('Dummy testing data seeded successfully!');
})->purpose('Seed the database with realistic dummy data for testing');

// Dynamic Backup Scheduler
Schedule::command('backup:run --scheduled')->dailyAt('02:00');
Schedule::command('backup:clean --days=30')->dailyAt('03:00');

// Subscription Billing Scheduler
Schedule::command('subscription:billing-run')->dailyAt('00:00');
