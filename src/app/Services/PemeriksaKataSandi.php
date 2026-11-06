<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Pengguna;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class PemeriksaKataSandi
{
    private static ?string $hashUmpan = null;

    /**
     * Bila akunnya tidak ada, perbandingan hash tetap dijalankan terhadap hash umpan.
     * Tanpa itu, surel yang tidak terdaftar dijawab jauh lebih cepat daripada kata sandi yang salah,
     * dan selisih waktu itu cukup untuk memetakan surel mana yang terdaftar.
     */
    public function cocok(?Pengguna $pengguna, string $kataSandi): bool
    {
        $hash = $pengguna?->kata_sandi ?? (self::$hashUmpan ??= Hash::make(Str::random(40)));

        return Hash::check($kataSandi, $hash) && $pengguna !== null;
    }
}
