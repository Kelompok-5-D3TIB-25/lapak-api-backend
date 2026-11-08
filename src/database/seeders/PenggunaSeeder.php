<?php

namespace Database\Seeders;

use App\Enums\NamaPeran;
use App\Models\Pengguna;
use App\Services\PengelolaPeran;
use Illuminate\Database\Seeder;

class PenggunaSeeder extends Seeder
{
    /** Kata sandi akun contoh. Hanya untuk mesin pengembangan. */
    public const SANDI_CONTOH = 'LapakContoh#2026';

    private const AKUN = [
        ['Admin Lapak', 'admin@lapak.test', NamaPeran::Admin],
        ['Staf Keuangan', 'keuangan@lapak.test', NamaPeran::Keuangan],
        ['Auditor Internal', 'auditor@lapak.test', NamaPeran::Auditor],
        ['Sri Wahyuni', 'sri@lapak.test', NamaPeran::Pembeli],
        ['Wulan Sari', 'wulan@lapak.test', NamaPeran::Pembeli],
        ['Eko Prasetyo', 'eko@lapak.test', NamaPeran::Pembeli],
        ['Tini Lestari', 'tini@lapak.test', NamaPeran::Pembeli],
        ['Andi Nugroho', 'andi@lapak.test', NamaPeran::Pembeli],
        ['Dewi Anggraini', 'dewi@lapak.test', NamaPeran::Pembeli],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('PenggunaSeeder dilewati: akun contoh tidak boleh ada di produksi.');

            return;
        }

        $pengelola = app(PengelolaPeran::class);

        foreach (self::AKUN as [$nama, $surel, $peran]) {
            $pengguna = Pengguna::updateOrCreate(
                ['surel' => $surel],
                ['nama' => $nama, 'kata_sandi' => self::SANDI_CONTOH],
            );

            $pengelola->beri($pengguna, $peran);
        }
    }
}
