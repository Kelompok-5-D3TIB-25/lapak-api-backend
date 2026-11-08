<?php

use App\Http\Controllers\KatalogController;
use App\Http\Controllers\ProdukSayaController;
use App\Http\Controllers\TokoSayaController;
use Illuminate\Support\Facades\Route;

// Katalog bersifat publik dan hanya-baca.
Route::get('produk', [KatalogController::class, 'index'])->name('produk.index');
Route::get('produk/{slug}', [KatalogController::class, 'show'])->name('produk.show');
Route::get('toko/{slug}', [KatalogController::class, 'toko'])->name('toko.show');

// Toko dan produk milik pengguna yang sedang masuk.
// Alamatnya tidak memuat pengenal toko: toko selalu ditentukan sesi.
use App\Http\Controllers\ModerasiTokoController;

// Toko dan produk milik pengguna yang sedang masuk.
// Alamatnya tidak memuat pengenal toko: toko selalu ditentukan sesi.
Route::middleware('auth:sesi')
    ->prefix('toko-saya')
    ->name('toko-saya.')
    ->whereNumber('id')
    ->group(function (): void {
        // Melihat dan membenahi toko sendiri tidak menuntut izin khusus: pemilik toko yang masih
        // menunggu persetujuan belum menjadi penjual, tetapi harus dapat memperbaiki datanya.
        Route::get('/', [TokoSayaController::class, 'show'])->name('show');
        Route::patch('/', [TokoSayaController::class, 'update'])->name('update');

        Route::post('/', [TokoSayaController::class, 'store'])
            ->middleware('izin:toko.buka')
            ->name('store');

        // Produk baru dapat dikelola setelah toko disetujui dan pemiliknya menjadi penjual.
        Route::middleware('izin:produk.kelola')->group(function (): void {
            Route::get('produk', [ProdukSayaController::class, 'index'])->name('produk.index');
            Route::post('produk', [ProdukSayaController::class, 'store'])->name('produk.store');
            Route::patch('produk/{id}', [ProdukSayaController::class, 'update'])->name('produk.update');
            Route::delete('produk/{id}', [ProdukSayaController::class, 'destroy'])
                ->name('produk.destroy');

            // Mengubah stok: wajib Idempotency-Key (LP-11). "Tambah 5" yang diulang karena
            // jaringan putus tidak boleh menjadi tambah 10.
            Route::post('produk/{id}/stok', [ProdukSayaController::class, 'stok'])
                ->middleware('idempoten')
                ->name('produk.stok');
        });
    });

// Pintu admin atas toko.
Route::middleware(['auth:sesi', 'izin:toko.moderasi'])
    ->prefix('admin/toko')
    ->name('admin.toko.')
    ->whereNumber('id')
    ->group(function (): void {
        Route::get('/', [ModerasiTokoController::class, 'index'])->name('index');

        // Menyetujui toko berarti memberi peran penjual: tindakan sensitif (LP-15).
        Route::middleware('sesi.segar')->group(function (): void {
            Route::post('{id}/setujui', [ModerasiTokoController::class, 'setujui'])->name('setujui');
            Route::post('{id}/tangguhkan', [ModerasiTokoController::class, 'tangguhkan'])
                ->name('tangguhkan');
        });
    });
