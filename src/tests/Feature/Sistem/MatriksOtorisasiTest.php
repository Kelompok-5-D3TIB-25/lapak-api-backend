<?php

use App\Enums\NamaPeran;
use App\Models\Pengguna;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
| Matriks otorisasi: siapa boleh memanggil rute mana.
|
| Setiap domain menuliskan harapannya pada tests/Feature/Sistem/matriks/<domain>.php.
| Uji ini (1) menuntut setiap rute terlindung memiliki baris, dan (2) memanggil setiap rute
| sebagai setiap peran, lalu membandingkan jawabannya dengan baris itu.
|
| Yang diperiksa hanya penolakan tingkat peran: 403 berkode DITOLAK. Jawaban lain (200, 404, 422,
| bahkan 403 LP-15) berarti peran itu lolos gerbang izin; benar-tidaknya isi jawaban diuji
| pada berkas uji domain masing-masing.
*/

/** @return array<string, list<string>|string> */
function matriksOtorisasi(): array
{
    $matriks = [];

    foreach (glob(__DIR__.'/matriks/*.php') as $berkas) {
        $matriks += require $berkas;
    }

    return $matriks;
}

/** @return list<string> "METODE alamat" seluruh rute /api/v1 yang berada di belakang auth:sesi */
function ruteTerlindung(): array
{
    $daftar = [];

    foreach (Route::getRoutes() as $rute) {
        $terlindung = in_array('auth:sesi', $rute->gatherMiddleware(), true);

        if (! str_starts_with($rute->uri(), 'api/v1') || ! $terlindung) {
            continue;
        }

        foreach (array_diff($rute->methods(), ['HEAD']) as $metode) {
            $daftar[] = "{$metode} {$rute->uri()}";
        }
    }

    return $daftar;
}

it('memiliki tepat satu baris matriks untuk setiap rute terlindung', function () {
    $rute = ruteTerlindung();
    $baris = array_keys(matriksOtorisasi());

    $tanpaBaris = array_values(array_diff($rute, $baris));
    $barisUsang = array_values(array_diff($baris, $rute));

    expect($tanpaBaris)->toBeEmpty('Rute tanpa baris matriks: '.implode(', ', $tanpaBaris))
        ->and($barisUsang)->toBeEmpty('Baris matriks tanpa rute: '.implode(', ', $barisUsang));
});

it('hanya menyebut peran yang dikenal', function () {
    $dikenal = array_map(fn (NamaPeran $peran) => $peran->value, NamaPeran::cases());

    $salah = [];

    foreach (matriksOtorisasi() as $rute => $boleh) {
        if ($boleh !== '*' && array_diff($boleh, $dikenal) !== []) {
            $salah[] = $rute;
        }
    }

    expect($salah)->toBeEmpty('Baris dengan peran tak dikenal: '.implode(', ', $salah));
});

it('menolak dan meloloskan setiap peran persis seperti matriks', function () {
    $pemegang = [null, ...NamaPeran::cases()];
    $selisih = [];

    foreach (matriksOtorisasi() as $rute => $boleh) {
        [$metode, $alamat] = explode(' ', $rute);
        $alamat = '/'.preg_replace('/\{[^}]+\}/', '999999', $alamat);

        foreach ($pemegang as $peran) {
            $pengguna = Pengguna::factory()->create();
            if ($peran !== null) {
                DB::table('pengguna_peran')->insert([
                    'pengguna_id' => $pengguna->id,
                    'peran_id' => DB::table('peran')->where('nama', $peran->value)->value('id'),
                ]);
            }

            $respons = $this->sebagai($pengguna)->json($metode, $alamat);

            $ditolak = $respons->status() === 403 && $respons->json('galat.kode') === 'DITOLAK';
            $harusBoleh = $boleh === '*' || ($peran !== null && in_array($peran->value, $boleh, true));

            if ($ditolak === $harusBoleh) {
                $selisih[] = sprintf(
                    '%s sebagai %s: seharusnya %s',
                    $rute,
                    $peran->value ?? 'tanpa peran',
                    $harusBoleh ? 'boleh' : 'ditolak',
                );
            }
        }
    }

    expect($selisih)->toBeEmpty("Tidak sesuai matriks:\n".implode("\n", $selisih));
});
