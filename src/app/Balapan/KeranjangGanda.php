<?php

declare(strict_types=1);

namespace App\Balapan;

use App\Models\Alamat;
use App\Models\Checkout;
use App\Models\Pengguna;
use App\Models\Produk;
use App\Models\Toko;
use App\Services\LayananKeranjang;
use App\Services\PenerbitSesi;
use Illuminate\Support\Str;

/**
 * Satu keranjang di-checkout dari banyak tab sekaligus, dengan kunci idempotensi berbeda.
 * Aturannya (LP-4, LP-11): tepat satu checkout lahir, dan stok hanya berkurang sesuai keranjang.
 */
final class KeranjangGanda implements SkenarioBalapan
{
    use PanggilHttp;

    private const STOK_AWAL = 10;

    public function keterangan(): string
    {
        return 'Satu keranjang di-checkout dari banyak tab, kunci berbeda: tepat satu checkout';
    }

    public function proses(): int
    {
        return 6;
    }

    public function siapkan(): array
    {
        $akhiran = Str::lower(Str::random(8));

        $penjual = Pengguna::factory()->penjual()->create([
            'nama' => 'Penjual Balapan',
            'surel' => "penjual-{$akhiran}@lapak.test",
        ]);
        $toko = Toko::factory()->for($penjual, 'pemilik')->create(['ongkir_rupiah' => 12_000]);
        $produk = Produk::factory()->for($toko)->create([
            'nama' => 'Kain Balapan', 'stok' => self::STOK_AWAL,
        ]);

        $pembeli = Pengguna::factory()->pembeli()->create([
            'nama' => 'Pembeli Balapan',
            'surel' => "pembeli-{$akhiran}@lapak.test",
        ]);
        $alamat = Alamat::factory()->for($pembeli, 'pengguna')->create(['utama' => true]);

        app(LayananKeranjang::class)->aturButir($pembeli, $produk->id, 2);

        // Kunci diterbitkan SETELAH peran diberikan: pemberian peran mencabut sesi lama.
        [, $kunci] = app(PenerbitSesi::class)->terbitkan($pembeli, 'Uji balapan');

        return [
            'pembeli' => $pembeli->id,
            'produk' => $produk->id,
            'alamat' => $alamat->id,
            'kunci' => $kunci,
        ];
    }

    public function jalankan(array $konteks, int $nomor): string
    {
        $badan = ['alamat_id' => $konteks['alamat']];

        [$status, $json] = $this->panggil('POST', '/api/v1/checkout', $badan, [
            'Authorization' => 'Bearer '.$konteks['kunci'],
            'Idempotency-Key' => "tab-{$nomor}-".Str::random(16),
        ]);

        return $this->ringkas($status, $json);
    }

    public function periksa(array $konteks, array $hasil): array
    {
        $checkout = Checkout::query()->where('pembeli_id', $konteks['pembeli'])->count();
        $stok = Produk::query()->findOrFail($konteks['produk'])->stok;

        $pelanggaran = [];
        if ($checkout !== 1) {
            $pelanggaran[] = "Lahir {$checkout} checkout dari satu keranjang, seharusnya 1.";
        }
        if ($stok !== self::STOK_AWAL - 2) {
            $terjual = self::STOK_AWAL - $stok;
            $pelanggaran[] = "Stok berkurang {$terjual}, padahal keranjang hanya berisi 2.";
        }

        return $pelanggaran;
    }
}
