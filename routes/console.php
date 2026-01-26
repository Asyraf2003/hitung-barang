<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reports:send daily --date=yesterday')
  ->dailyAt('00:00')
  ->timezone('Asia/Makassar')
  ->withoutOverlapping(30);

Schedule::command('reports:send weekly --date=yesterday')
  ->weeklyOn(1, '00:00') // Senin
  ->timezone('Asia/Makassar')
  ->withoutOverlapping(30);

Schedule::command('reports:send monthly --date=yesterday')
  ->monthlyOn(1, '00:00') // tanggal 1
  ->timezone('Asia/Makassar')
  ->withoutOverlapping(30);


