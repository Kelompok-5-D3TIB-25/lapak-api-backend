<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NamaPeran;
use App\Models\Pengguna;
use App\Models\Peran;
use Illuminate\Database\UniqueConstraintViolationException;
use LogicException;

/**
 * Satu-satunya pintu perubahan peran, seperti BukuBesar bagi saldo.
 *
 * Setiap perubahan peran diikuti dua hal yang tidak boleh terlewat:
 *   1. ingatan izin pengguna itu dilupakan, dan
 *   2. seluruh sesinya dicabut, sehingga ia masuk ulang dan menerima kunci baru.
 */
final class PengelolaPeran
{
    public function __construct(
        private readonly PemeriksaIzin $izin,
        private readonly PenerbitSesi $sesi,
    ) {}

    /**
     * @return bool false bila pengguna sudah memegang peran itu, sehingga tidak ada yang berubah
     */
    public function beri(Pengguna $pengguna, NamaPeran $peran, ?Pengguna $oleh = null): bool
    {
        $idPeran = $this->idPeran($peran);

        if ($pengguna->peran()->where('peran.id', $idPeran)->exists()) {
            return false;
        }

        try {
            $pengguna->peran()->attach($idPeran, ['diberikan_oleh' => $oleh?->id]);
        } catch (UniqueConstraintViolationException) {
            // Dua pemberian bersamaan: kunci utama pada pengguna_peran menolak yang kedua.
            return false;
        }

        $this->setelahBerubah($pengguna);

        return true;
    }

    /**
     * @return bool false bila pengguna memang tidak memegang peran itu
     */
    public function cabut(Pengguna $pengguna, NamaPeran $peran): bool
    {
        if ($pengguna->peran()->detach($this->idPeran($peran)) === 0) {
            return false;
        }

        $this->setelahBerubah($pengguna);

        return true;
    }

    /**
     * @return list<string> nama peran yang dipegang, urut abjad
     */
    public function daftar(Pengguna $pengguna): array
    {
        return $pengguna->peran()->orderBy('peran.nama')->pluck('peran.nama')->all();
    }

    private function setelahBerubah(Pengguna $pengguna): void
    {
        $this->izin->lupakan($pengguna);
        $this->sesi->cabutSemua($pengguna);
    }

    private function idPeran(NamaPeran $peran): int
    {
        $id = Peran::query()->where('nama', $peran->value)->value('id');

        if ($id === null) {
            throw new LogicException(
                "Peran {$peran->value} belum ada di basis data. Jalankan PeranIzinSeeder.",
            );
        }

        return (int) $id;
    }
}
