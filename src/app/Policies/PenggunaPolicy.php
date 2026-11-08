<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Pengguna;

/**
 * Middleware izin sudah memastikan pelaku memegang peran.kelola. Policy ini menjawab pertanyaan
 * berikutnya: terhadap AKUN YANG INI, bolehkah?
 */
final class PenggunaPolicy
{
    /**
     * Tidak seorang pun mengubah perannya sendiri. Akibatnya:
     *   - admin tidak dapat menambahkan peran keuangan ke akunnya lalu menyetujui uang, dan
     *   - admin terakhir tidak dapat mencabut dirinya: yang mencabut seorang admin selalu admin lain.
     */
    public function ubahPeran(Pengguna $pelaku, Pengguna $sasaran): bool
    {
        return ! $pelaku->is($sasaran);
    }
}
