<?php

use App\Http\Controllers\BukuBesarController;
use App\Http\Controllers\DompetController;
use Illuminate\Support\Facades\Route;

// Dompet milik sendiri: hanya pemegang izin dompet.lihat, yaitu penjual.
Route::middleware(['auth:sesi', 'izin:dompet.lihat'])->group(function (): void {
    Route::get('dompet', [DompetController::class, 'show'])->name('dompet.show');

    // Sesi segar: kunci yang sah saja belum cukup untuk membuka rincian uang (LP-15).
    Route::get('dompet/mutasi', [DompetController::class, 'mutasi'])
        ->middleware('sesi.segar')
        ->name('dompet.mutasi');
});

// Buku besar seluruh marketplace: staf pemegang izin buku-besar.lihat, selalu dengan sesi segar.
Route::middleware(['auth:sesi', 'izin:buku-besar.lihat', 'sesi.segar'])
    ->prefix('buku-besar')
    ->name('buku-besar.')
    ->whereNumber('id')
    ->group(function (): void {
        Route::get('dompet', [BukuBesarController::class, 'index'])->name('dompet.index');
        Route::get('dompet/{id}/mutasi', [BukuBesarController::class, 'mutasi'])->name('dompet.mutasi');
    });
