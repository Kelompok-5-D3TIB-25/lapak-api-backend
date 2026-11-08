<?php

use App\Http\Controllers\DompetController;
use Illuminate\Support\Facades\Route;

// Pintu HTTP pertama ke domain uang, dan seluruhnya di belakang kunci sesi.
Route::middleware('auth:sesi')->group(function (): void {
    Route::get('dompet', [DompetController::class, 'show'])->name('dompet.show');

    // Sesi segar: kunci yang sah saja belum cukup untuk membuka rincian uang (LP-15).
    Route::get('dompet/mutasi', [DompetController::class, 'mutasi'])
        ->middleware('sesi.segar')
        ->name('dompet.mutasi');
});
