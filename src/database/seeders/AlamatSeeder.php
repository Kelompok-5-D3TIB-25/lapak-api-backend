<?php

namespace Database\Seeders;

use App\Models\Alamat;
use App\Models\Pengguna;
use Illuminate\Database\Seeder;

class AlamatSeeder extends Seeder
{
    /** Alamat akun contoh (hanya untuk mesin pengembangan). */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('AlamatSeeder dilewati: alamat contoh tidak boleh ada di produksi.');

            return;
        }

        $andi = Pengguna::query()->where('surel', 'andi@lapak.test')->sole();
        $dewi = Pengguna::query()->where('surel', 'dewi@lapak.test')->sole();

        $buat = function (Pengguna $pemilik, array $data): void {
            Alamat::query()->updateOrCreate(
                ['pengguna_id' => $pemilik->id, 'label' => $data['label']],
                $data + ['pengguna_id' => $pemilik->id],
            );
        };

        $buat($andi, [
            'label' => 'Rumah',
            'nama_penerima' => 'Andi Nugroho',
            'telepon' => '081200000008',
            'jalan' => 'Jl. Slamet Riyadi No. 12',
            'kota' => 'Surakarta',
            'kode_pos' => '57111',
            'utama' => true,
        ]);
        $buat($andi, [
            'label' => 'Kantor',
            'nama_penerima' => 'Andi Nugroho',
            'telepon' => '081200000008',
            'jalan' => 'Jl. Ir. Sutami No. 36A',
            'kota' => 'Surakarta',
            'kode_pos' => '57126',
            'utama' => false,
        ]);
        $buat($dewi, [
            'label' => 'Rumah',
            'nama_penerima' => 'Dewi Anggraini',
            'telepon' => '081200000009',
            'jalan' => 'Jl. Slamet Riyadi No. 10',
            'kota' => 'Surakarta',
            'kode_pos' => '57111',
            'utama' => true,
        ]);
    }
}
