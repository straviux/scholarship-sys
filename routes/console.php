<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule conditional deadline processing
Schedule::command('scholarship:process-conditional-deadlines --send-reminders')
    ->dailyAt('09:00')
    ->name('process-conditional-deadlines')
    ->description('Process expired conditional approvals and send deadline reminders');

// Additional deadline check at end of business day
Schedule::command('scholarship:process-conditional-deadlines')
    ->dailyAt('18:00')
    ->name('process-expired-deadlines')
    ->description('Process expired conditional approvals');
