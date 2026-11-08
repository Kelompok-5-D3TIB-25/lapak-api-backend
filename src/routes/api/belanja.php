<?php

use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\SimulasiBelanjaController;
use Illuminate\Support\Facades\Route;

// Simulasi tetap publik: pengunjung boleh menghitung sebelum mendaftar.
Route::post('simulasi-belanja', SimulasiBelanjaController::class)->name('belanja.simulasi');

// Keranjang selalu milik pengguna yang sedang masuk. Checkout menyusul pada Sesi 4.
Route::middleware('auth:sesi')->group(function (): void {
    Route::get('keranjang', [KeranjangController::class, 'show'])->name('keranjang.show');
});
