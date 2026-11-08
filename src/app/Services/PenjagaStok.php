<?php

declare(strict_types=1);

namespace App\Services;

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

    /** Dipakai pembatalan. Menambah tidak memerlukan keputusan, maka cukup satu UPDATE. */
    public function kembalikan(int $idProduk, int $kuantitas): void
    {
        if ($kuantitas < 1) {
            throw new InvalidArgumentException('Kuantitas minimal 1.');
        }

        Produk::query()->whereKey($idProduk)->increment('stok', $kuantitas);
    }
}
