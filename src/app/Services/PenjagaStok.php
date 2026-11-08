<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\PenyesuaianStokDitolak;
use App\Exceptions\ProdukTidakDapatDibeli;
use App\Models\Produk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

/**
 * Satu-satunya pintu perubahan stok oleh transaksi (LP-4), seperti BukuBesar bagi saldo.
 * Checkout tidak pernah menulis kolom stok sendiri: ia mengambil produk lewat ambil(),
 * lalu mengurangi lewat kurangi().
 */
final class PenjagaStok
{
    /** Batas atas stok. Melindungi luapan aritmetika saat harga dikali kuantitas. */
    public const MAKSIMUM = 100_000;

    /**
     * Produk yang akan dibeli, beserta tokonya, berkunci nomor produk. Barisnya DIKUNCI sampai
     * transaksi pemanggil selesai.
     *
     * @param  list<int>  $idProduk
     * @return Collection<int, Produk>
     */
    public function ambil(array $idProduk): Collection
    {
        // Kunci baris hanya bertahan sampai transaksi selesai. Di luar transaksi, kunci dilepas
        // begitu SELECT ini selesai dan tidak melindungi apa pun.
        if (DB::transactionLevel() === 0) {
            throw new LogicException('PenjagaStok::ambil() harus di dalam DB::transaction().');
        }

        // lockForUpdate(): pembeli lain yang menginginkan baris yang sama MENUNGGU di sini sampai
        // transaksi ini selesai, lalu membaca stok yang sudah berkurang.
        // orderBy('id'): semua pembeli mengunci dengan urutan yang sama, apa pun urutan isi
        // keranjangnya, sehingga tidak ada dua transaksi yang saling menunggu selamanya.
        return Produk::query()
            ->with('toko')
            ->whereIn('id', $idProduk)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  Produk  $produk  hasil ambil(), bukan hasil kueri lain
     *
     * @throws ProdukTidakDapatDibeli 409 LP-4 bila stok tidak mencukupi
     */
    public function kurangi(Produk $produk, int $kuantitas): void
    {
        if ($kuantitas < 1) {
            throw new InvalidArgumentException('Kuantitas minimal 1.');
        }

        if ($produk->stok < $kuantitas) {
            throw ProdukTidakDapatDibeli::stokKurang(
                $produk->id, $produk->nama, $kuantitas, $produk->stok,
            );
        }

        $produk->stok -= $kuantitas;
        $produk->save();
    }

    /**
     * Dipakai penjual. Perubahannya RELATIF ("tambah 5"), tidak pernah mutlak ("jadikan 15").
     * Angka yang dilihat penjual di layarnya mungkin sudah usang ketika ia menekan simpan, dan
     * kunci baris tidak dapat melindungi pembacaan yang terjadi di layar orang.
     *
     * @throws PenyesuaianStokDitolak 409 LP-4 bila hasilnya negatif atau melewati batas
     */
    public function sesuaikan(int $idProduk, int $selisih): Produk
    {
        if ($selisih === 0) {
            throw new InvalidArgumentException('Selisih tidak boleh nol.');
        }

        return DB::transaction(function () use ($idProduk, $selisih): Produk {
            // Baris yang sama dengan yang dikunci ambil(): penjual dan pembeli saling menunggu.
            $produk = Produk::query()->lockForUpdate()->findOrFail($idProduk);
            $baru = $produk->stok + $selisih;

            if ($baru < 0) {
                throw PenyesuaianStokDitolak::akanNegatif(
                    $produk->id, $produk->nama, $produk->stok, $selisih,
                );
            }

            if ($baru > self::MAKSIMUM) {
                throw PenyesuaianStokDitolak::melewatiBatas(
                    $produk->id, $produk->nama, $produk->stok, self::MAKSIMUM,
                );
            }

            $produk->stok = $baru;
            $produk->save();

            return $produk;
        });
    }

    /** Dipakai pembatalan. Menambah tidak memerlukan keputusan, maka cukup satu UPDATE. */
    public function kembalikan(int $idProduk, int $kuantitas): void
    {
        if ($kuantitas < 1) {
            throw new InvalidArgumentException('Kuantitas minimal 1.');
        }

        Produk::query()->whereKey($idProduk)->increment('stok', $kuantitas);
    }
}
