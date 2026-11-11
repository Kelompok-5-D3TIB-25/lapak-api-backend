<?php

use App\Models\Pengguna;
use App\Models\Sesi;
use App\Services\PemeriksaSesi;
use App\Services\PenerbitSesi;

beforeEach(function () {
    $this->pengguna = Pengguna::factory()->create();
    $this->penerbit = app(PenerbitSesi::class);
    $this->pemeriksa = app(PemeriksaSesi::class);

    [$this->sesi, $this->kunci] = $this->penerbit->terbitkan($this->pengguna, 'Perangkat uji');
});

it('mengembalikan sesi untuk kunci yang valid', function () {
    $hasil = $this->pemeriksa->periksa($this->kunci);

    expect($hasil)->not->toBeNull()
        ->and($hasil->pengenal)->toBe($this->sesi->pengenal);
});

it('menolak kunci yang salah bentuk', function (string $kunci) {
    expect($this->pemeriksa->periksa($kunci))->toBeNull();
})->with([
    'kosong' => [''],
    'bukan kunci' => ['bukan-kunci'],
    'token jwt' => ['eyJhbGciOiJIUzI1NiJ9.e30.abc'],
    'null sebagai string' => ['null'],
]);

it('menolak sesi yang sudah dicabut', function () {
    $this->penerbit->cabut($this->sesi);

    expect($this->pemeriksa->periksa($this->kunci))->toBeNull();
});

it('menolak sesi yang menganggur lebih dari dua jam', function () {
    $this->travel(119)->minutes();
    expect($this->pemeriksa->periksa($this->kunci))->not->toBeNull();

    $this->travel(121)->minutes();
    expect($this->pemeriksa->periksa($this->kunci))->toBeNull();
});

it('menolak sesi berumur lebih dari tujuh hari walau terus dipakai', function () {
    // Dipakai setiap jam selama hampir tujuh hari: tidak pernah menganggur.
    foreach (range(1, 167) as $jam) {
        $this->travel(1)->hours();
        expect($this->pemeriksa->periksa($this->kunci))->not->toBeNull();
    }

    $this->travel(2)->hours();
    expect($this->pemeriksa->periksa($this->kunci))->toBeNull();
});

it('menolak kunci milik akun yang ditangguhkan', function () {
    $this->pengguna->forceFill(['status' => 'ditangguhkan'])->save();

    expect($this->pemeriksa->periksa($this->kunci))->toBeNull();
});

it('mencatat pemakaian paling sering satu kali per menit', function () {
    $awal = $this->sesi->terakhir_dipakai_pada;

    $this->travel(30)->seconds();
    $this->pemeriksa->periksa($this->kunci);
    expect($this->sesi->refresh()->terakhir_dipakai_pada->equalTo($awal))->toBeTrue();

    $this->travel(31)->seconds();
    $this->pemeriksa->periksa($this->kunci);
    expect($this->sesi->refresh()->terakhir_dipakai_pada->gt($awal))->toBeTrue();
});

it('mematikan kunci lama saat dirotasi tanpa mengganti sesinya', function () {
    $kunciBaru = $this->penerbit->rotasi($this->sesi);

    expect($this->pemeriksa->periksa($this->kunci))->toBeNull()
        ->and($this->pemeriksa->periksa($kunciBaru)?->pengenal)->toBe($this->sesi->pengenal)
        ->and(Sesi::count())->toBe(1);
});
