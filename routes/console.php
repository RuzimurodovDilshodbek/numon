<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\CheckLessonReminders;
use App\Console\Commands\SendPaymentReminders;
use App\Console\Commands\GenerateMonthlyLessons;
use App\Console\Commands\MarkOverduePayments;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Har 5 daqiqada dars eslatmalarini tekshirish
Schedule::command(CheckLessonReminders::class)->everyFiveMinutes();

// Har kuni ertalab 9:00 da to'lov eslatmalari
Schedule::command(SendPaymentReminders::class)->dailyAt('09:00');

// Har kuni tunda muddati o'tganlarni belgilash
Schedule::command(MarkOverduePayments::class)->dailyAt('00:05');

// Har oyning 25-sida keyingi oy darslarini yaratish
Schedule::command(GenerateMonthlyLessons::class)->monthlyOn(25, '10:00');
