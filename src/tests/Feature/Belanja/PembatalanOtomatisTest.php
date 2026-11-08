<?php

use App\Domain\Pesanan\StatusPesanan;
use App\Enums\StatusPembayaran;
use App\Models\Alamat;
use App\Models\ButirPesanan;
use App\Models\Checkout;
use App\Models\Pembayaran;
use App\Models\Pengguna;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Toko;
use App\Services\LayananKeranjang;
use App\Services\PembatalanOtomatis;
use Illuminate\Support\Facades\DB;

/**
 * Checkout menunggu pembayaran yang batas bayarnya sudah lewat.
 * Mengembalikan [checkout, tagihan, produk].
 *
 * @return array{0: Checkout, 1: Pembayaran, 2: list<Produk>}
 */
function checkoutMenunggu(array $timpaTagihan = [], int $stok = 5): array
{
    $pembeli = Pengguna::factory()->pembeli()->create();
    $penjual = Pengguna::factory()->penjual()->create();
    $toko = Toko::factory()->for($penjual, 'pemilik')->create(['ongkir_rupiah' => 5_000]);
    $produk = Produk::factory()->for($toko)->create(['stok' => $stok, 'harga_rupiah' => 100_000]);

    $alamat = Alamat::factory()->for($pembeli, 'pengguna')->create();

    app(LayananKeranjang::class)->aturButir($pembeli, $produk->id, 2);

    test()->sebagai($pembeli)
        ->postJson('/api/v1/checkout', ['alamat_id' => $alamat->id], [
            'Idempotency-Key' => 'checkout-batal-uji-000001',
        ])
        ->assertCreated();

    $checkout = Checkout::sole();
    $tagihan = Pembayaran::query()->where('checkout_id', $checkout->id)->sole();
    $tagihan->forceFill($timpaTagihan + ['batas_bayar_pada' => now()->subMinute()])->save();

    return [$checkout, $tagihan, [$produk]];
}

it('membatalkan checkout yang lewat batas bayar, mengembalikan stok, dan menandai tagihan kedaluwarsa', function () {
    [$checkout, $tagihan, $produk] = checkoutMenunggu();

    $this->artisan('lapak:batalkan-kedaluwarsa')
        ->expectsOutputToContain('1 checkout dibatalkan')
        ->assertSuccessful();

    expect($checkout->pesanan()->pluck('status')->unique()->all())
        ->toBe([StatusPesanan::Dibatalkan])
        ->and($checkout->pesanan()->sole()->alasan_batal)->toBe('Tidak dibayar sampai batas waktu')
        ->and($tagihan->refresh()->status)->toBe(StatusPembayaran::Kedaluwarsa)
        ->and($produk[0]->refresh()->stok)->toBe(5);
});

it('aman dijalankan berulang: stok tidak dikembalikan dua kali', function () {
    [, , $produk] = checkoutMenunggu(['batas_bayar_pada' => now()->subMinute()]);

    $this->artisan('lapak:batalkan-kedaluwarsa')->assertSuccessful();
    $this->artisan('lapak:batalkan-kedaluwarsa')
        ->expectsOutputToContain('0 checkout dibatalkan')
        ->assertSuccessful();

    expect($produk[0]->refresh()->stok)->toBe(5);
});

it('memeriksa ulang tagihan di bawah gembok sebelum membatalkan', function () {
    [$checkout, $tagihan, $produk] = checkoutMenunggu(['batas_bayar_pada' => now()->subMinute()]);

    // Penjadwal sudah memilih checkout ini, tetapi pembayarannya masuk sesaat sebelum gembok.
    $tagihan->forceFill(['status' => StatusPembayaran::Lunas])->save();

    expect(app(PembatalanOtomatis::class)->batalkan($checkout->id))->toBeFalse()
        ->and($checkout->pesanan()->pluck('status')->unique()->all())
        ->toBe([StatusPesanan::MenungguPembayaran])
        ->and($produk[0]->refresh()->stok)->toBe(3);
});

it('tidak membatalkan tagihan yang masih punya waktu', function () {
    $pembeli = Pengguna::factory()->pembeli()->create();
    $penjual = Pengguna::factory()->penjual()->create();
    $toko = Toko::factory()->for($penjual, 'pemilik')->create();
    $produk = Produk::factory()->for($toko)->create(['stok' => 5, 'harga_rupiah' => 100_000]);
    $alamat = Alamat::factory()->for($pembeli, 'pengguna')->create();

    app(LayananKeranjang::class)->aturButir($pembeli, $produk->id, 1);

    $this->sebagai($pembeli)
        ->postJson('/api/v1/checkout', ['alamat_id' => $alamat->id], [
            'Idempotency-Key' => 'checkout-batal-uji-000002',
        ])
        ->assertCreated();

    $this->artisan('lapak:batalkan-kedaluwarsa')
        ->expectsOutputToContain('0 checkout dibatalkan')
        ->assertSuccessful();

    expect($produk->refresh()->stok)->toBe(4)
        ->and(Pesanan::sole()->status)->toBe(StatusPesanan::MenungguPembayaran);
});

it('mengembalikan stok urut nomor produk, apa pun urutan butirnya', function () {
    $checkout = Checkout::factory()->create();
    Pembayaran::factory()->for($checkout)->create(['batas_bayar_pada' => now()->subMinute()]);
    $pesanan = Pesanan::factory()->for($checkout)->create();
    [$kecil, $tengah, $besar] = Produk::factory()->count(3)->create(['stok' => 0])->all();

    // Butir sengaja disimpan dari nomor produk terbesar.
    foreach ([$besar, $kecil, $tengah] as $barang) {
        ButirPesanan::factory()->for($pesanan)->create(['produk_id' => $barang->id]);
    }

    DB::enableQueryLog();
    app(PembatalanOtomatis::class)->batalkan($checkout->id);

    $urutan = collect(DB::getQueryLog())
        // Tanda kutip nama tabel berbeda antara SQLite dan MySQL, maka dicocokkan dengan titik.
        ->filter(fn (array $kueri) => preg_match('/^update .produk. /', $kueri['query']) === 1)
        ->map(fn (array $kueri) => last($kueri['bindings']))
        ->values()
        ->all();

    expect($urutan)->toBe([$kecil->id, $tengah->id, $besar->id]);
});
