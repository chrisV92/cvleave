<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('leave:send-reminders')->dailyAt('08:00');
Schedule::command('tasks:send-reminders')->dailyAt('08:05');

// Monday morning, after the daily run, so a task due today is not announced
// twice within five minutes.
Schedule::command('tasks:send-weekly-digest')->weeklyOn(1, '08:15');
