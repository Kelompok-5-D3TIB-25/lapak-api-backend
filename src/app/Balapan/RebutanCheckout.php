<?php

declare(strict_types=1);

namespace App\Balapan;

use App\Models\Alamat;
use App\Models\Checkout;
use App\Models\Pembayaran;
use App\Models\Pengguna;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Toko;
use App\Services\LayananKeranjang;
use App\Services\PenerbitSesi;
use Illuminate\Support\Str;

/**
 * Dua belas pembeli berbeda, masing-masing dengan sesi, keranjang, dan alamatnya sendiri,
 * berebut lima barang yang sama pada saat yang sama.
 * Aturannya: lima checkout lahir, tujuh ditolak tanpa sisa; tidak ada checkout setengah jadi.
 */
final class RebutanCheckout implements SkenarioBalapan
{
    use PanggilHttp;

    private const STOK_AWAL = 5;

    public function keterangan(): string
    {
        return 'Dua belas pembeli checkout serentak lewat HTTP untuk 5 barang';
    }

    public function proses(): int
    {
        return 12;
    }

    public function siapkan(): array
    {
        $akhiran = Str::lower(Str::random(8));
        $penjual = Pengguna::factory()->penjual()->create([
            'surel' => "penjual-{$akhiran}@lapak.test",
        ]);
        $toko = Toko::factory()->for($penjual, 'pemilik')->create(['ongkir_rupiah' => 10_000]);
        $produk = Produk::factory()->for($toko)->create([
            'nama' => 'Kain Rebutan Checkout', 'stok' => self::STOK_AWAL, 'harga_rupiah' => 185_000,
        ]);

        $peserta = [];
        for ($ke = 1; $ke <= $this->proses(); $ke++) {
            $surel = "pembeli-{$akhiran}-{$ke}@lapak.test";
            $pembeli = Pengguna::factory()->pembeli()->create([
                'nama' => "Pembeli {$ke}",
                'surel' => $surel,
            ]);
            $alamat = Alamat::factory()->for($pembeli, 'pengguna')->create();

            app(LayananKeranjang::class)->aturButir($pembeli, $produk->id, 1);

            [, $kunciSesi] = app(PenerbitSesi::class)->terbitkan($pembeli, 'Uji balapan');

            $peserta[] = [
                'pembeli' => $pembeli->id,
                'alamat' => $alamat->id,
                'kunci_sesi' => $kunciSesi,
                'kunci_idempotensi' => "balapan-checkout-{$ke}-".Str::lower(Str::random(12)),
            ];
        }

        return ['produk' => $produk->id, 'peserta' => $peserta];
    }

    public function jalankan(array $konteks, int $nomor): string
    {
        $saya = $konteks['peserta'][$nomor - 1];
        $badan = ['alamat_id' => $saya['alamat']];

        [$status, $json] = $this->panggil('POST', '/api/v1/checkout', $badan, [
            'Authorization' => 'Bearer '.$saya['kunci_sesi'],
            'Idempotency-Key' => $saya['kunci_idempotensi'],
        ]);

        return $this->ringkas($status, $json);
    }

    public function periksa(array $konteks, array $hasil): array
    {
        $produk = Produk::query()->findOrFail($konteks['produk']);
        $pembeli = collect($konteks['peserta'])->pluck('pembeli')->all();
        $idCheckout = Checkout::query()->whereIn('pembeli_id', $pembeli)->pluck('id');
        $checkout = $idCheckout->count();
        $pesanan = Pesanan::query()->whereIn('pembeli_id', $pembeli)->count();
        $tagihan = Pembayaran::query()->whereIn('checkout_id', $idCheckout)->count();
        $terbeli = count(array_filter($hasil, fn (string $h) => $h === '201'));

        $pelanggaran = [];
        if ($terbeli !== self::STOK_AWAL) {
            $pelanggaran[] = "{$terbeli} checkout berhasil, seharusnya tepat ".self::STOK_AWAL.'.';
        }
        if ($checkout !== $terbeli || $pesanan !== $terbeli || $tagihan !== $terbeli) {
            $pelanggaran[] = "Tidak seimbang: {$checkout} checkout, {$pesanan} pesanan, {$tagihan} tagihan untuk {$terbeli} jawaban 201.";
        }
        if ($produk->stok !== 0) {
            $pelanggaran[] = "Sisa stok {$produk->stok}, seharusnya 0.";
        }

        return $pelanggaran;
    }
}
