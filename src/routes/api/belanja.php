<?php

use App\Http\Controllers\SimulasiBelanjaController;
use Illuminate\Support\Facades\Route;

// Keranjang yang terikat pengguna menyusul pada Sesi 2; checkout pada Sesi 4.
Route::post('simulasi-belanja', SimulasiBelanjaController::class)->name('belanja.simulasi');
