<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Belanja\ButirBermasalah;
use App\Domain\Belanja\RincianToko;
use App\Exceptions\GalatDomain;
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

        return $this->penyusun->pilah($butir, $pengguna);
    }

    /**
     * Menetapkan kuantitas sebuah produk MENJADI angka tertentu, bukan menambahkannya.
     * Permintaan yang sama dikirim dua kali menghasilkan keranjang yang sama.
     */
    public function aturButir(Pengguna $pengguna, int $produkId, int $kuantitas): void
    {
        // Versi tegas: melempar LP-4 atau LP-5 sebelum apa pun ditulis.
        $this->penyusun->susun([['produk_id' => $produkId, 'kuantitas' => $kuantitas]], $pengguna);

        $keranjang = $this->milik($pengguna);
        $sudahAda = $keranjang->butir()->where('produk_id', $produkId)->exists();

        if (! $sudahAda && $keranjang->butir()->count() >= config('lapak.belanja.butir_maksimum')) {
            throw new GalatDomain('KERANJANG_PENUH', 'Keranjang sudah penuh.', 422);
        }

        $keranjang->butir()->updateOrCreate(['produk_id' => $produkId], ['kuantitas' => $kuantitas]);
    }

    /** Menghapus butir yang tidak ada bukan galat: hasil akhirnya sama, butir itu tidak ada. */
    public function hapusButir(Pengguna $pengguna, int $produkId): void
    {
        $this->milik($pengguna)->butir()->where('produk_id', $produkId)->delete();
    }

    public function kosongkan(Pengguna $pengguna): void
    {
        $this->milik($pengguna)->butir()->delete();
    }

    /** Keranjang SELALU dicari lewat pengguna sesi. Tidak ada jalan menuju keranjang lewat id. */
    private function milik(Pengguna $pengguna): Keranjang
    {
        $keranjang = Keranjang::query()->where('pengguna_id', $pengguna->id)->first();

        if ($keranjang === null) {
            $keranjang = new Keranjang;
            $keranjang->pengguna_id = $pengguna->id;
            $keranjang->save();
        }

        return $keranjang;
    }
}
