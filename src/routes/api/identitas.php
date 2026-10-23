<?php

use App\Http\Controllers\RegistrasiController;
use Illuminate\Support\Facades\Route;

// Login, logout, dan daftar sesi menyusul pada Sesi 2.
Route::post('registrasi', RegistrasiController::class)->name('registrasi');
