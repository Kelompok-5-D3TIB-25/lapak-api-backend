<?php

use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\SimulasiBelanjaController;
use Illuminate\Support\Facades\Route;

// Simulasi tetap publik: pengunjung boleh menghitung sebelum mendaftar.
Route::post('simulasi-belanja', SimulasiBelanjaController::class)->name('belanja.simulasi');

// Keranjang selalu milik pengguna yang sedang masuk. Checkout menyusul pada Sesi 4.
Route::middleware('auth:sesi')
    ->prefix('keranjang')
    ->name('keranjang.')
    ->whereNumber('produkId')
    ->group(function (): void {
        Route::get('/', [KeranjangController::class, 'show'])->name('show');
        Route::delete('/', [KeranjangController::class, 'kosongkan'])->name('kosongkan');

        // PUT, bukan POST: "jadikan kuantitas produk ini N". Mengulang permintaan tidak menggandakan isi.
        Route::put('butir/{produkId}', [KeranjangController::class, 'simpanButir'])->name('simpan');
        Route::delete('butir/{produkId}', [KeranjangController::class, 'hapusButir'])->name('hapus');
    });
