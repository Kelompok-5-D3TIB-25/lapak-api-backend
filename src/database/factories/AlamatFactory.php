<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Alamat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alamat>
 */
final class AlamatFactory extends Factory
{
    protected $model = Alamat::class;

    public function definition(): array
    {
        return [
            'pengguna_id' => PenggunaFactory::new(),
            'label' => 'Rumah',
            'nama_penerima' => 'Andi Nugroho',
            'telepon' => '081200000008',
            'jalan' => 'Jl. Slamet Riyadi No. 12',
            'kota' => 'Surakarta',
            'kode_pos' => '57111',
            'utama' => true,
        ];
    }

    /** Alamat kedua: bukan utama, label kantor, jalan berbeda. */
    public function cadangan(): static
    {
        return $this->state(['label' => 'Kantor', 'jalan' => 'Jl. Ir. Sutami No. 36A', 'utama' => false]);
    }
}
