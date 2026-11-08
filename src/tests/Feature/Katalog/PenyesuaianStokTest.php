<?php

use App\Models\KunciIdempotensi;
use App\Models\Pengguna;
use App\Models\Produk;
use App\Models\Toko;
use App\Services\PenjagaStok;

beforeEach(function () {
    $this->sri = Pengguna::factory()->penjual()->create();
    $this->tokoSri = Toko::factory()->for($this->sri, 'pemilik')->create();
    $this->kain = Produk::factory()->for($this->tokoSri)->create([
        'nama' => 'Kain Batik Cap', 'stok' => 10,
    ]);
    $this->alamatStok = "/api/v1/toko-saya/produk/{$this->kain->id}/stok";

    $this->sesuaikan = fn (mixed $selisih, string $kunci = 'restok-kain-0001') => $this->postJson(
        $this->alamatStok, ['selisih' => $selisih], ['Idempotency-Key' => $kunci],
    );
});

it('menambah dan mengurangi stok dengan selisih', function () {
    $this->sebagai($this->sri);

    ($this->sesuaikan)(5)->assertOk()->assertJsonPath('data.stok', 15);
    ($this->sesuaikan)(-12, 'koreksi-kain-0001')->assertOk()->assertJsonPath('data.stok', 3);

    expect($this->kain->refresh()->stok)->toBe(3);
});

it('menghitung dari stok terkini, bukan dari angka yang pernah dibaca', function () {
    // Penjual memuat halaman ketika stok 10. Sebelum ia menekan simpan, tiga barang terjual.
    $usang = Produk::findOrFail($this->kain->id);
    Produk::whereKey($this->kain->id)->decrement('stok', 3);

    $hasil = app(PenjagaStok::class)->sesuaikan($usang->id, 5);

    expect($usang->stok)->toBe(10)
        ->and($hasil->stok)->toBe(12)
        ->and($this->kain->refresh()->stok)->toBe(12);
});

it('menolak pengurangan yang membuat stok negatif dengan 409 (LP-4)', function () {
    $this->sebagai($this->sri);

    ($this->sesuaikan)(-11)
        ->assertConflict()
        ->assertJsonPath('galat.kode', 'LP-4')
        ->assertJsonPath('galat.rincian.stok_sekarang', 10);

    expect($this->kain->refresh()->stok)->toBe(10)
        ->and(KunciIdempotensi::count())->toBe(0);
});

it('menolak permintaan tanpa Idempotency-Key (LP-11)', function () {
    $this->sebagai($this->sri)
        ->postJson($this->alamatStok, ['selisih' => 5])
        ->assertUnprocessable()
        ->assertJsonPath('galat.kode', 'LP-11');
});

it('mewajibkan Idempotency-Key dan hanya menambah satu kali saat diulang (LP-11)', function () {
    $this->sebagai($this->sri);

    ($this->sesuaikan)(5)->assertOk()->assertHeaderMissing('Idempotency-Replayed');
    ($this->sesuaikan)(5)
        ->assertOk()
        ->assertHeader('Idempotency-Replayed', 'true')
        ->assertJsonPath('data.stok', 15);

    expect($this->kain->refresh()->stok)->toBe(15);
});

it('menjawab 404 untuk produk toko lain dan tidak mengubah stoknya', function () {
    $milikLain = Produk::factory()->create(['stok' => 7]);

    $this->sebagai($this->sri)
        ->postJson("/api/v1/toko-saya/produk/{$milikLain->id}/stok", ['selisih' => 5], [
            'Idempotency-Key' => 'restok-kain-0001',
        ])
        ->assertNotFound();

    expect($milikLain->refresh()->stok)->toBe(7);
});

it('menjawab 403 untuk pembeli, dan 401 tanpa sesi', function () {
    $pembeli = Pengguna::factory()->pembeli()->create();

    $this->postJson($this->alamatStok, ['selisih' => 5], [
        'Idempotency-Key' => 'restok-kain-0001',
    ])->assertUnauthorized();

    $this->sebagai($pembeli)
        ->postJson($this->alamatStok, ['selisih' => 5], [
            'Idempotency-Key' => 'restok-kain-0001',
        ])
        ->assertForbidden()
        ->assertJsonPath('galat.kode', 'DITOLAK');
});

it('tidak lagi menerima stok mutlak lewat PATCH produk', function () {
    $alamat = "/api/v1/toko-saya/produk/{$this->kain->id}";

    $this->sebagai($this->sri)
        ->patchJson($alamat, ['nama' => 'Kain Batik Tulis', 'stok' => 99])
        ->assertUnprocessable()
        ->assertJsonPath(
            'galat.rincian.stok.0',
            'Ubah stok dengan selisih lewat POST /toko-saya/produk/{id}/stok.',
        );

    expect($this->kain->refresh())->stok->toBe(10)->nama->toBe('Kain Batik Cap');

    $this->patchJson($alamat, ['nama' => 'Kain Batik Tulis'])
        ->assertOk()
        ->assertJsonPath('data.stok', 10);
});
