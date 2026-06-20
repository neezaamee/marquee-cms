<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:seed-dummy', function () {
    $this->info('Starting dummy testing data seeder...');
    $this->call('db:seed', ['--class' => 'DummyDataSeeder']);
    $this->info('Dummy testing data seeded successfully!');
})->purpose('Seed the database with realistic dummy data for testing');
