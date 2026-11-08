<?php

use App\Enums\NamaIzin;
use App\Enums\NamaPeran;
use App\Models\Pengguna;
use App\Services\PemeriksaIzin;
use Database\Seeders\PeranIzinSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/** Memberi peran langsung di basis data. Hanya untuk uji lapisan izin itu sendiri. */
function beriPeranLangsung(Pengguna $pengguna, NamaPeran ...$peran): Pengguna
{
    foreach ($peran as $nama) {
        DB::table('pengguna_peran')->insert([
            'pengguna_id' => $pengguna->id,
            'peran_id' => DB::table('peran')->where('nama', $nama->value)->value('id'),
        ]);
    }

    return $pengguna;
}

beforeEach(function () {
    Route::middleware(['api', 'auth:sesi', 'izin:produk.kelola'])
        ->get('/api/_uji/kelola-produk', fn () => ['ok' => true]);
    Route::middleware(['api', 'auth:sesi', 'izin:buku-besar.lihat'])
        ->get('/api/_uji/buku-besar', fn () => ['ok' => true]);

    $this->pemeriksa = app(PemeriksaIzin::class);
});

it('menyalin pemetaan peran ke izin dari enum ke basis data, persis sama', function () {
    foreach (NamaPeran::cases() as $peran) {
        $diBasisData = DB::table('izin')
            ->join('peran_izin', 'peran_izin.izin_id', '=', 'izin.id')
            ->join('peran', 'peran.id', '=', 'peran_izin.peran_id')
            ->where('peran.nama', $peran->value)
            ->pluck('izin.nama')
            ->all();

        $diEnum = array_map(fn (NamaIzin $izin) => $izin->value, $peran->izinBawaan());

        expect($diBasisData)->toEqualCanonicalizing($diEnum);
    }
});

it('dapat menjalankan seeder berulang kali tanpa menggandakan apa pun', function () {
    $this->seed(PeranIzinSeeder::class);

    expect(DB::table('peran')->count())->toBe(count(NamaPeran::cases()))
        ->and(DB::table('izin')->count())->toBe(count(NamaIzin::cases()));
});

it('menggabungkan izin dari seluruh peran yang dipegang (LP-2)', function () {
    $sri = beriPeranLangsung(Pengguna::factory()->create(), NamaPeran::Pembeli, NamaPeran::Penjual);

    expect($this->pemeriksa->daftar($sri))->toBe([
        'dompet.lihat', 'keranjang.kelola', 'produk.kelola', 'toko.buka',
    ]);
});

it('tidak memberi izin apa pun kepada pengguna tanpa peran', function () {
    expect($this->pemeriksa->daftar(Pengguna::factory()->create()))->toBe([]);
});

it('tidak memberi admin izin atas uang', function () {
    $admin = beriPeranLangsung(Pengguna::factory()->create(), NamaPeran::Admin);

    expect($this->pemeriksa->punya($admin, NamaIzin::PeranKelola))->toBeTrue()
        ->and($this->pemeriksa->punya($admin, NamaIzin::BukuBesarLihat))->toBeFalse()
        ->and($this->pemeriksa->punya($admin, NamaIzin::DompetLihat))->toBeFalse();
});

it('menjawab 401 sebelum memeriksa izin bila kunci tidak ada', function () {
    $this->getJson('/api/_uji/kelola-produk')->assertUnauthorized();
});

it('menjawab 403 berbentuk kontrak bila peran tidak memegang izinnya', function () {
    $pembeli = beriPeranLangsung(Pengguna::factory()->create(), NamaPeran::Pembeli);

    $this->sebagai($pembeli)->getJson('/api/_uji/kelola-produk')
        ->assertForbidden()
        ->assertExactJson(['sukses' => false, 'galat' => [
            'kode' => 'DITOLAK',
            'pesan' => 'Anda tidak berwenang melakukan tindakan ini.',
            'rincian' => null,
        ]]);
});

it('memeriksa izin sebelum mencari objek yang disebut alamat', function () {
    Route::middleware(['api', 'auth:sesi', 'izin:peran.kelola'])
        ->get('/api/_uji/pengguna/{pengguna}', fn (Pengguna $pengguna) => ['id' => $pengguna->id]);

    $pembeli = beriPeranLangsung(Pengguna::factory()->create(), NamaPeran::Pembeli);
    $admin = beriPeranLangsung(Pengguna::factory()->create(), NamaPeran::Admin);

    $this->sebagai($pembeli)->getJson("/api/_uji/pengguna/{$admin->id}")->assertForbidden();
    $this->sebagai($pembeli)->getJson('/api/_uji/pengguna/999999')->assertForbidden();

    $this->sebagai($admin)->getJson('/api/_uji/pengguna/999999')->assertNotFound();
});

it('meloloskan peran yang memegang izinnya', function () {
    $penjual = beriPeranLangsung(Pengguna::factory()->create(), NamaPeran::Penjual);

    $this->sebagai($penjual)->getJson('/api/_uji/kelola-produk')->assertOk();
});

it('meloloskan setiap peran yang memegang izin itu, apa pun nama perannya (LP-2)', function (
    NamaPeran $peran,
    int $status,
) {
    $pengguna = beriPeranLangsung(Pengguna::factory()->create(), $peran);

    $this->sebagai($pengguna)->getJson('/api/_uji/buku-besar')->assertStatus($status);
})->with([
    'keuangan' => [NamaPeran::Keuangan, 200],
    'auditor' => [NamaPeran::Auditor, 200],
    'penjual' => [NamaPeran::Penjual, 403],
    'pembeli' => [NamaPeran::Pembeli, 403],
    'admin' => [NamaPeran::Admin, 403],
]);

it('gagal keras bila rute menyebut izin yang tidak dikenal', function () {
    Route::middleware(['api', 'auth:sesi', 'izin:produk.kelolaa'])
        ->get('/api/_uji/salah-ketik', fn () => 'ok');

    $this->sebagai(Pengguna::factory()->create())
        ->getJson('/api/_uji/salah-ketik')
        ->assertStatus(500);
});
