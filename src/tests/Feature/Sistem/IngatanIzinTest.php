<?php

use App\Enums\NamaIzin;
use App\Enums\NamaPeran;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Services\PemeriksaIzin;
use App\Services\PengelolaPeran;
use Database\Seeders\PeranIzinSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->pemeriksa = app(PemeriksaIzin::class);
    $this->sri = Pengguna::factory()->penjual()->create();
});

function hitungKueriIzin(Closure $kerja): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $kerja();
    DB::disableQueryLog();

    $menyentuhIzin = fn (array $kueri): bool => str_contains($kueri['query'], 'peran_izin');

    return count(array_filter(DB::getQueryLog(), $menyentuhIzin));
}

it('hanya sekali bertanya ke basis data untuk pertanyaan yang berulang', function () {
    $kueri = hitungKueriIzin(function () {
        $this->pemeriksa->punya($this->sri, NamaIzin::ProdukKelola);
        $this->pemeriksa->punya($this->sri, NamaIzin::DompetLihat);
        $this->pemeriksa->daftar($this->sri);
    });

    expect($kueri)->toBe(1);
});

it('tidak mencampur ingatan dua pengguna', function () {
    $tini = Pengguna::factory()->pembeli()->create();

    expect($this->pemeriksa->punya($this->sri, NamaIzin::ProdukKelola))->toBeTrue()
        ->and($this->pemeriksa->punya($tini, NamaIzin::ProdukKelola))->toBeFalse();
});

it('langsung melupakan izin lama begitu peran berubah lewat PengelolaPeran', function () {
    expect($this->pemeriksa->punya($this->sri, NamaIzin::ProdukKelola))->toBeTrue();

    app(PengelolaPeran::class)->cabut($this->sri, NamaPeran::Penjual);
    expect($this->pemeriksa->punya($this->sri, NamaIzin::ProdukKelola))->toBeFalse();

    app(PengelolaPeran::class)->beri($this->sri, NamaPeran::Auditor);
    expect($this->pemeriksa->punya($this->sri, NamaIzin::BukuBesarLihat))->toBeTrue();
});

it('masih mengingat izin lama bila peran diubah di luar PengelolaPeran', function () {
    expect($this->pemeriksa->punya($this->sri, NamaIzin::ProdukKelola))->toBeTrue();

    $this->sri->peran()->detach(Peran::query()->where('nama', 'penjual')->value('id'));
    expect($this->pemeriksa->punya($this->sri, NamaIzin::ProdukKelola))->toBeTrue();

    $this->travel(config('lapak.otorisasi.ingat_menit') + 1)->minutes();
    expect($this->pemeriksa->punya($this->sri, NamaIzin::ProdukKelola))->toBeFalse();
});

it('melupakan semua ingatan ketika pemetaan peran ke izin disalin ulang', function () {
    expect($this->pemeriksa->punya($this->sri, NamaIzin::ProdukKelola))->toBeTrue();

    DB::table('peran_izin')
        ->where('izin_id', DB::table('izin')->where('nama', 'produk.kelola')->value('id'))
        ->delete();
    expect($this->pemeriksa->punya($this->sri, NamaIzin::ProdukKelola))->toBeTrue();

    $this->pemeriksa->lupakanSemua();
    expect($this->pemeriksa->punya($this->sri, NamaIzin::ProdukKelola))->toBeFalse();

    $this->seed(PeranIzinSeeder::class);
    expect($this->pemeriksa->punya($this->sri, NamaIzin::ProdukKelola))->toBeTrue();
});

it('mencetak matriks izin dari basis data', function () {
    $this->artisan('lapak:matriks-izin')
        ->expectsOutputToContain('buku-besar.lihat')
        ->assertSuccessful();
});
