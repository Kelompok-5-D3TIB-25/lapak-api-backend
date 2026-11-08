<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Belanja\AlasanBermasalah;
use App\Domain\Belanja\BarisBelanja;
use App\Domain\Belanja\ButirBermasalah;
use App\Domain\Belanja\PenghitungBelanja;
use App\Domain\Belanja\RincianToko;
use App\Domain\Uang;
use App\Exceptions\ProdukTidakDapatDibeli;
use App\Models\Pengguna;
use App\Models\Produk;
use Illuminate\Support\Collection;

/**
 * Jembatan antara basis data dan rumus murni: mengambil harga dan stok TERKINI dari server,
 * lalu menyerahkannya ke PenghitungBelanja. Klien hanya menyebut produk dan kuantitas (LP-6).
 */
final class PenyusunBelanja
{
    /**
     * Memilah butir menjadi yang dapat dihitung dan yang bermasalah, tanpa melempar galat.
     * Dipakai keranjang: isi keranjang bisa menjadi basi, dan membukanya tidak boleh gagal.
     *
     * Bila $diTangan diisi, tidak ada kueri produk di sini. Harga dan stok dihitung dari baris
     * yang sudah diambil pemanggil lewat PenjagaStok, yaitu baris yang sedang dikuncinya.
     *
     * @param  list<array{produk_id: int, kuantitas: int}>  $butir
     * @param  Pengguna|null  $pembeli  bila diisi, produk dari toko miliknya ikut ditandai (LP-5)
     * @param  Collection<int, Produk>|null  $diTangan  lihat keterangan di atas
     * @return array{0: list<RincianToko>, 1: list<ButirBermasalah>}
     */
    public function pilah(
        array $butir,
        ?Pengguna $pembeli = null,
        ?Collection $diTangan = null,
    ): array {
        $diminta = collect($butir)->pluck('kuantitas', 'produk_id');

        $produk = $diTangan !== null
            ? $diTangan->filter(fn (Produk $barang) => $barang->sedangTampil())->keyBy('id')
            : Produk::query()
                ->tampil()
                ->with('toko')
                ->whereIn('id', $diminta->keys())
                ->get()
                ->keyBy('id');

        $baris = [];
        $toko = [];
        $bermasalah = [];

        foreach ($diminta as $produkId => $kuantitas) {
            $barang = $produk[$produkId] ?? null;

            if ($barang === null) {
                $bermasalah[] = new ButirBermasalah(
                    $produkId, $kuantitas, AlasanBermasalah::TidakTersedia,
                );

                continue;
            }

            if ($pembeli !== null && $barang->dimilikiOleh($pembeli)) {
                $bermasalah[] = new ButirBermasalah(
                    $produkId, $kuantitas, AlasanBermasalah::ProdukSendiri, $barang->nama,
                );

                continue;
            }

            if ($barang->stok < $kuantitas) {
                $bermasalah[] = new ButirBermasalah(
                    $produkId, $kuantitas, AlasanBermasalah::StokKurang, $barang->nama, $barang->stok,
                );

                continue;
            }

            $baris[] = new BarisBelanja(
                produkId: $barang->id,
                namaProduk: $barang->nama,
                tokoId: $barang->toko_id,
                hargaSatuan: Uang::dari($barang->harga_rupiah),
                kuantitas: $kuantitas,
            );
            $toko[$barang->toko_id] = [
                'nama' => $barang->toko->nama,
                'ongkir' => Uang::dari($barang->toko->ongkir_rupiah),
            ];
        }

        $rincian = $baris === []
            ? []
            : (new PenghitungBelanja(config('lapak.komisi_persen')))->hitung($baris, $toko);

        return [$rincian, $bermasalah];
    }

    /**
     * Versi tegas: satu butir bermasalah menggagalkan seluruhnya.
     * Dipakai simulasi belanja, pengisian keranjang, dan checkout.
     *
     * @param  list<array{produk_id: int, kuantitas: int}>  $butir
     * @param  Collection<int, Produk>|null  $diTangan  lihat pilah()
     * @return list<RincianToko>
     */
    public function susun(
        array $butir,
        ?Pengguna $pembeli = null,
        ?Collection $diTangan = null,
    ): array {
        [$rincian, $bermasalah] = $this->pilah($butir, $pembeli, $diTangan);

        $hilang = [];
        foreach ($bermasalah as $masalah) {
            if ($masalah->alasan === AlasanBermasalah::TidakTersedia) {
                $hilang[] = $masalah->produkId;
            }
        }
        if ($hilang !== []) {
            throw ProdukTidakDapatDibeli::tidakTampil($hilang);
        }

        foreach ($bermasalah as $masalah) {
            if ($masalah->alasan === AlasanBermasalah::ProdukSendiri) {
                throw ProdukTidakDapatDibeli::milikSendiri(
                    $masalah->produkId, (string) $masalah->namaProduk,
                );
            }

            throw ProdukTidakDapatDibeli::stokKurang(
                $masalah->produkId,
                (string) $masalah->namaProduk,
                $masalah->kuantitas,
                (int) $masalah->stokTersedia,
            );
        }

        return $rincian;
    }
}
