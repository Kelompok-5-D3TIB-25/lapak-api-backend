<?php

use App\Domain\Pesanan\PenomorPesanan;
use App\Domain\Pesanan\StatusPesanan;
use App\Exceptions\ProdukTidakDapatDibeli;
use App\Models\Alamat;
use App\Models\ButirKeranjang;
use App\Models\ButirPesanan;
use App\Models\Checkout;
use App\Models\Keranjang;
use App\Models\KunciIdempotensi;
use App\Models\Pembayaran;
use App\Models\Pengguna;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Toko;
use App\Services\LayananKeranjang;

/*
| Keadaan awal setiap uji: Andi memiliki alamat dan keranjang berisi dua toko.
|   Batik Bu Sri   : 2 x Kain Batik Cap @ 185.000, ongkir 12.000 -> 382.000
|   Oleh-Oleh Wulan: 3 x Intip Goreng @ 32.500, ongkir 10.000 -> 107.500
|                                                       total bayar 489.500
*/
beforeEach(function () {
    $this->andi = Pengguna::factory()->pembeli()->create();
    $this->alamat = Alamat::factory()->for($this->andi, 'pengguna')->create([
        'nama_penerima' => 'Andi Nugroho', 'jalan' => 'Jl. Slamet Riyadi No. 12',
    ]);

    $this->sri = Pengguna::factory()->penjual()->create();
    $this->batik = Toko::factory()->for($this->sri, 'pemilik')
        ->create(['nama' => 'Batik Bu Sri', 'ongkir_rupiah' => 12_000]);
    $this->kain = Produk::factory()->for($this->batik)
        ->create(['nama' => 'Kain Batik Cap', 'harga_rupiah' => 185_000, 'stok' => 5]);

    $this->oleh = Toko::factory()->create(['nama' => 'Oleh-Oleh Wulan', 'ongkir_rupiah' => 10_000]);
    $this->intip = Produk::factory()->for($this->oleh)
        ->create(['nama' => 'Intip Goreng', 'harga_rupiah' => 32_500, 'stok' => 10]);

    $keranjang = app(LayananKeranjang::class);
    $keranjang->aturButir($this->andi, $this->kain->id, 2);
    $keranjang->aturButir($this->andi, $this->intip->id, 3);

    $this->checkout = fn (array $badan = [], string $kunci = 'checkout-uji-0000000001') => $this
        ->postJson(
            '/api/v1/checkout',
            $badan + ['alamat_id' => $this->alamat->id],
            ['Idempotency-Key' => $kunci],
        );
});

it('menolak permintaan tanpa kunci sesi, tanpa izin, atau tanpa Idempotency-Key', function () {
    $badan = ['alamat_id' => $this->alamat->id];

    $this->postJson('/api/v1/checkout', $badan)->assertUnauthorized();

    $this->sebagai(Pengguna::factory()->admin()->create());
    ($this->checkout)()->assertForbidden()->assertJsonPath('galat.kode', 'DITOLAK');

    $this->sebagai($this->andi)->postJson('/api/v1/checkout', $badan)
        ->assertUnprocessable()
        ->assertJsonPath('galat.kode', 'LP-11');

    expect(Checkout::count())->toBe(0);
});

it('melahirkan satu pesanan per toko dan satu tagihan untuk semuanya (LP-7)', function () {
    $respons = $this->sebagai($this->andi);
    $respons = ($this->checkout)()
        ->assertCreated()
        ->assertJsonPath('data.total', ['rupiah' => 489_500, 'tampilan' => 'Rp 489.500'])
        ->assertJsonPath('data.pembayaran.status', 'menunggu')
        ->assertJsonPath('data.pembayaran.jumlah.rupiah', 489_500)
        ->assertJsonCount(2, 'data.pesanan')
        ->assertJsonPath('data.pesanan.0.toko.nama', 'Batik Bu Sri')
        ->assertJsonPath('data.pesanan.0.status', 'menunggu_pembayaran')
        ->assertJsonPath('data.pesanan.0.total.rupiah', 382_000)
        ->assertJsonPath('data.pesanan.1.total.rupiah', 107_500);

    $checkout = Checkout::sole();

    expect($checkout->total_rupiah)->toBe(489_500)
        ->and($checkout->pengenal_sesi)->toBe($this->sesiUji->pengenal)
        ->and(Pembayaran::sole()->jumlah_rupiah)->toBe(489_500)
        ->and(Pesanan::count())->toBe(2)
        ->and($respons->json('data.pesanan.0.nomor'))->toMatch(PenomorPesanan::POLA)
        // Jumlah seluruh pesanan selalu sama dengan jumlah tagihannya.
        ->and((int) Pesanan::sum('total_rupiah'))->toBe(489_500);
});

it('mengurangi stok dan mengosongkan keranjang dalam langkah yang sama (LP-4)', function () {
    $this->sebagai($this->andi);
    ($this->checkout)()->assertCreated();

    expect($this->kain->refresh()->stok)->toBe(3)
        ->and($this->intip->refresh()->stok)->toBe(7)
        ->and(ButirKeranjang::count())->toBe(0);

    $this->getJson('/api/v1/keranjang')->assertJsonPath('data.pesanan', []);
});

it('mengabaikan harga, kuantitas, total, dan pembeli kiriman klien (LP-6)', function () {
    $orangLain = Pengguna::factory()->pembeli()->create();
    $this->sebagai($this->andi);

    ($this->checkout)([
        'total' => 1, 'total_rupiah' => 1, 'pembeli_id' => $orangLain->id, 'status' => 'dibayar',
        'butir' => [['produk_id' => $this->kain->id, 'kuantitas' => 1, 'harga_satuan' => 1]],
    ])
        ->assertCreated()
        ->assertJsonPath('data.total.rupiah', 489_500)
        ->assertJsonPath('data.pesanan.0.status', 'menunggu_pembayaran');

    expect(Checkout::sole()->pembeli_id)->toBe($this->andi->id)
        ->and(ButirPesanan::query()->where('produk_id', $this->kain->id)->value('kuantitas'))
        ->toBe(2);
});

it('membatalkan SELURUH checkout bila satu butir kehabisan stok', function () {
    // Di antara mengisi keranjang dan menekan bayar, stok kain tinggal satu.
    $this->kain->forceFill(['stok' => 1])->save();
    $this->sebagai($this->andi);

    ($this->checkout)()
        ->assertConflict()
        ->assertJsonPath('galat.kode', 'LP-4')
        ->assertJsonPath('galat.rincian.tersedia', 1);

    // Tidak ada yang tertulis separuh: pesanan toko kedua pun tidak lahir, stoknya utuh,
    // keranjang masih berisi, dan Idempotency-Key tidak tercatat.
    expect(Checkout::count())->toBe(0)
        ->and(Pesanan::count())->toBe(0)
        ->and(Pembayaran::count())->toBe(0)
        ->and($this->intip->refresh()->stok)->toBe(10)
        ->and(ButirKeranjang::count())->toBe(2)
        ->and(KunciIdempotensi::count())->toBe(0);
});

it('tidak menyisakan apa pun bila langkah terakhir gagal', function () {
    // Tagihan dibuat paling akhir. Saat pembuatannya gagal, checkout, kedua pesanan, butirnya,
    // dan pengurangan stok SUDAH ditulis di dalam transaksi. Semuanya harus ikut batal.
    Pembayaran::creating(fn () => throw new RuntimeException('Tabel pembayaran bermasalah.'));
    $this->sebagai($this->andi);

    ($this->checkout)()->assertStatus(500)->assertJsonPath('galat.kode', 'GALAT_SERVER');

    expect(Checkout::count())->toBe(0)
        ->and(Pesanan::count())->toBe(0)
        ->and(ButirPesanan::count())->toBe(0)
        ->and($this->kain->refresh()->stok)->toBe(5)
        ->and($this->intip->refresh()->stok)->toBe(10)
        ->and(ButirKeranjang::count())->toBe(2)
        ->and(KunciIdempotensi::count())->toBe(0);
});

it('menjawab pengulangan dengan respons yang sama dan efek satu kali (LP-11)', function () {
    $this->sebagai($this->andi);

    $pertama = ($this->checkout)()->assertCreated();
    $kedua = ($this->checkout)()->assertCreated()->assertHeader('Idempotency-Replayed', 'true');

    expect($kedua->json('data.kode'))->toBe($pertama->json('data.kode'))
        ->and(Checkout::count())->toBe(1)
        ->and(Pesanan::count())->toBe(2)
        ->and($this->kain->refresh()->stok)->toBe(3);
});

it('menolak checkout kedua atas keranjang yang sudah kosong walau kuncinya baru', function () {
    $this->sebagai($this->andi);

    ($this->checkout)()->assertCreated();
    ($this->checkout)([], 'checkout-uji-0000000002')
        ->assertUnprocessable()
        ->assertJsonPath('galat.kode', 'KERANJANG_KOSONG');

    expect(Checkout::count())->toBe(1);
});

it('menuntut sesi segar untuk checkout bernilai besar (LP-15)', function () {
    config(['lapak.transaksi.batas_segar_rupiah' => 400_000]);
    $this->sebagai($this->andi, segar: false);

    ($this->checkout)()->assertForbidden()->assertJsonPath('galat.kode', 'LP-15');

    expect(Checkout::count())->toBe(0)
        ->and($this->kain->refresh()->stok)->toBe(5)
        ->and(ButirKeranjang::count())->toBe(2);

    // Setelah sesi segar, kunci idempotensi yang sama boleh dipakai lagi.
    $this->sebagai($this->andi);
    ($this->checkout)()->assertCreated();
});

it('menjawab 404 untuk alamat milik orang lain tanpa mengubah apa pun', function () {
    $dewi = Pengguna::factory()->pembeli()->create();
    $alamatDewi = Alamat::factory()->for($dewi, 'pengguna')->create();

    $this->sebagai($this->andi);
    ($this->checkout)(['alamat_id' => $alamatDewi->id])
        ->assertUnprocessable()
        ->assertJsonPath('galat.rincian.alamat_id.0', 'Alamat tidak ditemukan.');

    expect(Checkout::count())->toBe(0);
});

it('menyalin alamat kirim pada pesanan, bukan menunjuk ke tabel alamat', function () {
    $this->sebagai($this->andi);
    ($this->checkout)()->assertCreated();

    $pesanan = Pesanan::query()->orderBy('id')->firstOrFail();

    expect($pesanan->alamat_kirim)->toMatchArray([
        'nama_penerima' => 'Andi Nugroho', 'jalan' => 'Jl. Slamet Riyadi No. 12',
    ])->and(array_key_exists('alamat_id', $pesanan->getAttributes()))->toBeFalse();
});

it('melihat pesanan sendiri di GET /pesanan dan GET /pesanan/{nomor}', function () {
    $this->sebagai($this->andi);
    ($this->checkout)()->assertCreated();

    $this->getJson('/api/v1/pesanan')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $nomor = Pesanan::query()->orderBy('id')->first()->nomor;

    $this->getJson("/api/v1/pesanan/{$nomor}")
        ->assertOk()
        ->assertJsonPath('data.pesanan.nomor', $nomor)
        ->assertJsonPath('data.pembayaran.status', 'menunggu');
});

it('menjawab 404 untuk pesanan orang lain dan nomor yang tidak ada', function () {
    $this->sebagai($this->andi);
    ($this->checkout)()->assertCreated();
    $nomor = Pesanan::query()->orderBy('id')->firstOrFail()->nomor;

    $dewi = Pengguna::factory()->pembeli()->create();
    $this->sebagai($dewi);

    $this->getJson("/api/v1/pesanan/{$nomor}")->assertNotFound();
    $this->getJson('/api/v1/pesanan/LPK-20260101-999999')->assertNotFound();
    $this->getJson('/api/v1/pesanan/bukan-nomor')->assertNotFound();
});

it('menandai pesanan menunggu_pembayaran, bukan dibayar, saat checkout', function () {
    $this->sebagai($this->andi);
    ($this->checkout)()->assertCreated();

    expect(Pesanan::query()->pluck('status')->unique()->all())
        ->toBe([StatusPesanan::MenungguPembayaran]);
});

it('menolak produk toko sendiri di keranjang saat checkout (LP-5)', function () {
    // Sri (pemilik toko) mengisi keranjang dengan produk tokonya sendiri lalu mencoba checkout.
    $sri = $this->batik->pemilik;
    $keranjang = app(LayananKeranjang::class);

    // Sri mengisi keranjangnya dengan cara yang sama seperti pembeli lain — macet di LP-5.
    expect(fn () => $keranjang->aturButir($sri, $this->kain->id, 1))
        ->toThrow(ProdukTidakDapatDibeli::class);
});

it('memakai PenjagaStok untuk mengurangi stok, bukan menulis kolom stok sendiri', function () {
    $this->sebagai($this->andi);
    ($this->checkout)()->assertCreated();

    expect($this->kain->refresh()->stok)->toBe(3);
});

it('memberi nomor pesanan unik yang berurutan seumur hidup sistem', function () {
    $this->sebagai($this->andi);
    ($this->checkout)()->assertCreated();

    $nomor = Pesanan::query()->orderBy('id')->pluck('nomor')->all();

    expect($nomor)->toHaveCount(2)
        ->and($nomor[0])->toMatch(PenomorPesanan::POLA)
        ->and($nomor[1])->toMatch(PenomorPesanan::POLA)
        ->and($nomor[0])->not->toBe($nomor[1]);
});
