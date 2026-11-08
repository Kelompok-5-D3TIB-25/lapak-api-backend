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
 * Enam permintaan dengan Idempotency-Key yang SAMA, tiba serentak.
 * Aturannya: tepat satu checkout lahir, dan keenamnya menerima kode checkout yang sama.
 */
final class KunciGanda implements SkenarioBalapan
{
    use PanggilHttp;

    private const STOK_AWAL = 50;

    public function keterangan(): string
    {
        return 'Checkout yang sama diulang serentak dengan satu Idempotency-Key: efeknya satu kali';
    }

    public function proses(): int
    {
        return 6;
    }

    public function siapkan(): array
    {
        $akhiran = Str::lower(Str::random(8));
        $toko = Toko::factory()->create(['slug' => "toko-{$akhiran}"]);
        $produk = Produk::factory()->for($toko)->create([
            'nama' => 'Kain Kunci Ganda', 'stok' => self::STOK_AWAL,
        ]);

        $pembeli = Pengguna::factory()->pembeli()->create([
            'surel' => "pembeli-{$akhiran}@lapak.test",
        ]);
        $alamat = Alamat::factory()->for($pembeli, 'pengguna')->create();

        app(LayananKeranjang::class)->aturButir($pembeli, $produk->id, 2);

        [, $kunciSesi] = app(PenerbitSesi::class)->terbitkan($pembeli, 'Uji balapan');

        return [
            'pembeli' => $pembeli->id,
            'produk' => $produk->id,
            'alamat' => $alamat->id,
            'kunci_sesi' => $kunciSesi,
            'kunci_idempotensi' => 'balapan-kunci-'.Str::lower(Str::random(16)),
        ];
    }

    public function jalankan(array $konteks, int $nomor): string
    {
        $badan = ['alamat_id' => $konteks['alamat']];

        [$status, $json, $header] = $this->panggil('POST', '/api/v1/checkout', $badan, [
            'Authorization' => 'Bearer '.$konteks['kunci_sesi'],
            'Idempotency-Key' => $konteks['kunci_idempotensi'],
        ]);

        if ($status !== 201) {
            return $this->ringkas($status, $json);
        }

        $jenis = isset($header['idempotency-replayed']) ? 'ulangan' : 'pertama';

        // Kode checkout ikut dicatat: semua proses harus menerima checkout YANG SAMA.
        return "201 {$jenis} ".substr($json['data']['kode'], -6);
    }

    public function periksa(array $konteks, array $hasil): array
    {
        $checkout = Checkout::query()->where('pembeli_id', $konteks['pembeli'])->count();
        $stok = Produk::query()->findOrFail($konteks['produk'])->stok;
        $pertama = count(array_filter(
            $hasil,
            fn (string $h) => str_starts_with($h, '201 pertama'),
        ));
        $kode = array_unique(array_map(fn (string $h) => substr($h, -6), $hasil));

        $pelanggaran = [];
        if ($checkout !== 1) {
            $pelanggaran[] = "Lahir {$checkout} checkout dari satu Idempotency-Key, seharusnya 1.";
        }
        if ($pertama !== 1) {
            $pelanggaran[] = "{$pertama} proses merasa menjadi yang pertama, seharusnya tepat 1.";
        }
        if (count($kode) !== 1) {
            $pelanggaran[] = 'Proses yang berbeda menerima jawaban yang berbeda.';
        }
        if ($stok !== 48) {
            $pelanggaran[] = 'Stok berkurang '.(50 - $stok).', padahal keranjang hanya berisi 2.';
        }

        return $pelanggaran;
    }
}
