<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\NamaPeran;
use App\Models\Pengguna;
use App\Services\PengelolaPeran;
use Illuminate\Console\Command;

final class BeriPeran extends Command
{
    protected $signature = 'lapak:beri-peran
        {surel : Surel akun yang sudah terdaftar}
        {peran : Nama peran, misalnya admin}';

    protected $description = 'Memberi peran kepada sebuah akun dari baris perintah server';

    public function handle(PengelolaPeran $pengelola): int
    {
        $peran = NamaPeran::tryFrom((string) $this->argument('peran'));
        $pengguna = Pengguna::query()->where('surel', $this->argument('surel'))->first();

        if ($peran === null || $pengguna === null) {
            $this->error($peran === null ? 'Peran tidak dikenal.' : 'Akun tidak ditemukan.');

            return self::FAILURE;
        }

        $berubah = $pengelola->beri($pengguna, $peran);

        $this->info($berubah
            ? "{$pengguna->surel} kini memegang peran {$peran->value}. Seluruh sesinya dicabut."
            : "{$pengguna->surel} sudah memegang peran {$peran->value}. Tidak ada yang berubah.");

        return self::SUCCESS;
    }
}
