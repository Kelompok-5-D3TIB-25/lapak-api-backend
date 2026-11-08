<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\NamaIzin;
use App\Enums\NamaPeran;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class MatriksIzin extends Command
{
    protected $signature = 'lapak:matriks-izin';

    protected $description = 'Menampilkan izin setiap peran menurut isi basis data';

    public function handle(): int
    {
        $punya = DB::table('peran_izin')
            ->join('peran', 'peran.id', '=', 'peran_izin.peran_id')
            ->join('izin', 'izin.id', '=', 'peran_izin.izin_id')
            ->get(['peran.nama as peran', 'izin.nama as izin'])
            ->map(fn (object $baris) => "{$baris->peran}|{$baris->izin}")
            ->flip();

        $peran = array_map(fn (NamaPeran $p) => $p->value, NamaPeran::cases());

        $baris = array_map(fn (NamaIzin $izin) => [
            $izin->value,
            ...array_map(fn (string $p) => $punya->has("{$p}|{$izin->value}") ? 'v' : '', $peran),
        ], NamaIzin::cases());

        $this->table(['izin', ...$peran], $baris);

        return self::SUCCESS;
    }
}
