<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('insights:generate {--silent}', function () {
    $summary = app(\App\Support\SystemInsightGenerator::class)->generate();

    if (! $this->option('silent')) {
        $this->info("Insights generated. Created: {$summary['created']}, updated: {$summary['updated']}, unchanged: {$summary['unchanged']}.");
    }

    return 0;
})->purpose('Generate operational system insights');

Schedule::command('insights:generate --silent')
    ->hourly()
    ->withoutOverlapping();
