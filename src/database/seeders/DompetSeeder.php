<?php

namespace Database\Seeders;

use App\Enums\JenisDompet;
use App\Services\BukuBesar;
use Illuminate\Database\Seeder;

class DompetSeeder extends Seeder
{
    /**
     * Hanya tiga dompet sistem. Dompet penjual lahir saat pertama kali dibutuhkan,
     * dan tidak ada saldo awal: saldo hanya boleh berasal dari mutasi.
     */
    public function run(BukuBesar $buku): void
    {
        foreach ([JenisDompet::Eksternal, JenisDompet::Escrow, JenisDompet::Pendapatan] as $jenis) {
            $buku->dompetSistem($jenis);
        }
    }
}
