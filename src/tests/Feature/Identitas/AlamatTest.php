<?php

use App\Models\Alamat;
use App\Models\Pengguna;

beforeEach(function () {
    $this->andi = Pengguna::factory()->pembeli()->create();
});

it('mendaftar alamat milik sendiri saja, alamat utama lebih dulu', function () {
    Alamat::factory()->cadangan()->for($this->andi, 'pengguna')
        ->create(['jalan' => 'Jl. Kantor 1']);
    Alamat::factory()->for($this->andi, 'pengguna')->create(['jalan' => 'Jl. Rumah 2']);
    Alamat::factory()->create(['jalan' => 'Jl. Orang Lain 3']);

    $this->sebagai($this->andi)->getJson('/api/v1/alamat')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.jalan', 'Jl. Rumah 2')
        ->assertJsonPath('data.0.utama', true)
        ->assertJsonPath('data.1.jalan', 'Jl. Kantor 1')
        ->assertJsonMissingPath('data.0.pengguna_id');
});

it('memotret alamat tanpa membawa pengenal dan label', function () {
    $alamat = Alamat::factory()->for($this->andi, 'pengguna')->create([
        'nama_penerima' => 'Andi Nugroho', 'telepon' => '081200000008',
        'jalan' => 'Jl. Slamet Riyadi No. 12', 'kota' => 'Surakarta', 'kode_pos' => '57111',
    ]);

    expect($alamat->potret())->toBe([
        'nama_penerima' => 'Andi Nugroho',
        'telepon' => '081200000008',
        'jalan' => 'Jl. Slamet Riyadi No. 12',
        'kota' => 'Surakarta',
        'kode_pos' => '57111',
    ]);
});
