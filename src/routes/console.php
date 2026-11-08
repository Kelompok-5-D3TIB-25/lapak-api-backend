<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sesi yang sudah lama mati dibuang setiap dini hari.
Schedule::command('lapak:pangkas-sesi')->dailyAt('03:15');

// LP-10: checkout yang lewat batas bayar dibatalkan dan stoknya dikembalikan.
Schedule::command('lapak:batalkan-kedaluwarsa')->everyTenMinutes()->withoutOverlapping();

// Commands registered via bootstrap/app.php ->withCommands() (Laravel 11 style). See bootstrap/app.php.
