<?php

use App\Exceptions\GalatDomain;
use App\Http\Middleware\IdPermintaan;
use Illuminate\Support\Facades\Route;

it('melaporkan sehat beserta id permintaan', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertJsonPath('sukses', true)
        ->assertJsonPath('data.status', 'sehat')
        ->assertHeader(IdPermintaan::HEADER);
});

it('mengembalikan 404 berbentuk kontrak, bukan halaman HTML', function () {
    $this->get('/api/v1/alamat-yang-tidak-ada')    // sengaja tanpa header Accept
        ->assertNotFound()
        ->assertExactJson(['sukses' => false, 'galat' => [
            'kode' => 'TIDAK_DITEMUKAN',
            'pesan' => 'Sumber daya tidak ditemukan.',
            'rincian' => null,
        ]]);
});

it('mengembalikan 405 berbentuk kontrak', function () {
    $this->deleteJson('/api/v1/health')
        ->assertStatus(405)
        ->assertJsonPath('galat.kode', 'METODE_TIDAK_DIIZINKAN');
});

it('meneruskan kode aturan dari GalatDomain', function () {
    Route::middleware('api')->get('/api/_uji/galat-domain', function () {
        throw new GalatDomain('LP-13', 'Saldo tidak cukup.', 409, ['kurang' => 5000]);
    });

    $this->getJson('/api/_uji/galat-domain')
        ->assertStatus(409)
        ->assertExactJson(['sukses' => false, 'galat' => [
            'kode' => 'LP-13',
            'pesan' => 'Saldo tidak cukup.',
            'rincian' => ['kurang' => 5000],
        ]]);
});

it('menyembunyikan rincian galat tak terduga saat debug mati', function () {
    config(['app.debug' => false]);
    Route::middleware('api')->get('/api/_uji/meledak', function () {
        throw new RuntimeException('SQLSTATE: kata sandi basis data bocor');
    });

    $this->getJson('/api/_uji/meledak')
        ->assertStatus(500)
        ->assertJsonPath('galat.kode', 'GALAT_SERVER')
        ->assertJsonPath('galat.rincian', null)
        ->assertDontSee('SQLSTATE');
});

it('melarang penyimpanan respons API di cache', function () {
    $sehat = $this->getJson('/api/v1/health')->assertOk();
    $ditolak = $this->getJson('/api/v1/saya')->assertUnauthorized();

    expect($sehat->headers->get('Cache-Control'))->toContain('no-store')
        ->and($ditolak->headers->get('Cache-Control'))->toContain('no-store');
});

it('menyertakan header baku pada 401 dan 429', function () {
    $this->getJson('/api/v1/saya')->assertUnauthorized()->assertHeader('WWW-Authenticate', 'Bearer');

    foreach (range(1, 5) as $ke) {
        $this->postJson('/api/v1/masuk', ['surel' => 'x@lapak.test', 'kata_sandi' => "tebakan-{$ke}"]);
    }

    $keenam = ['surel' => 'x@lapak.test', 'kata_sandi' => 'tebakan-6'];

    $respons = $this->postJson('/api/v1/masuk', $keenam)
        ->assertTooManyRequests()
        ->assertJsonPath('galat.kode', 'TERLALU_SERING');

    expect((int) $respons->headers->get('Retry-After'))->toBeGreaterThan(0)->toBeLessThanOrEqual(60);
});
