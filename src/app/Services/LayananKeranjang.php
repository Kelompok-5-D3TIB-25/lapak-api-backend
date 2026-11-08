<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Belanja\ButirBermasalah;
use App\Domain\Belanja\RincianToko;
use App\Models\ButirKeranjang;
use App\Models\Keranjang;
use App\Models\Pengguna;

final class LayananKeranjang
{
    public function __construct(private readonly PenyusunBelanja $penyusun) {}

    /**
     * Keranjang tidak menyimpan harga. Setiap kali dibuka, harga dan stok dibaca ulang
     * dari tabel produk, sehingga angka yang tampil selalu angka hari ini (LP-6).
     *
     * @return array{0: list<RincianToko>, 1: list<ButirBermasalah>}
     */
    public function lihat(Pengguna $pengguna): array
    {
        $keranjang = Keranjang::query()->where('pengguna_id', $pengguna->id)->with('butir')->first();

        if ($keranjang === null || $keranjang->butir->isEmpty()) {
            return [[], []];
        }

        $butir = $keranjang->butir
            ->sortBy('id')
            ->map(fn (ButirKeranjang $b) => ['produk_id' => $b->produk_id, 'kuantitas' => $b->kuantitas])
            ->values()
            ->all();

        return $this->penyusun->pilah($butir);
    }
}
