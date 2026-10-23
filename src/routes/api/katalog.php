<?php

use App\Http\Controllers\KatalogController;
use Illuminate\Support\Facades\Route;

// Katalog bersifat publik dan hanya-baca. Endpoint tulis toko dan produk menyusul
// pada Sesi 2 dan 3, setelah identitas dan otorisasi tersedia.
Route::get('produk', [KatalogController::class, 'index'])->name('produk.index');
Route::get('produk/{slug}', [KatalogController::class, 'show'])->name('produk.show');
Route::get('toko/{slug}', [KatalogController::class, 'toko'])->name('toko.show');
