<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ButirPesanan;
use App\Models\Pesanan;
use App\Models\Produk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ButirPesanan>
 */
final class ButirPesananFactory extends Factory
{
    protected $model = ButirPesanan::class;

    public function definition(): array
    {
        $harga = 185_000;
        $kuantitas = 1;

        return [
            'pesanan_id' => Pesanan::factory(),
            'produk_id' => Produk::factory(),
            'nama_produk' => 'Kain Batik Cap Sogan',
            'harga_rupiah' => $harga,
            'kuantitas' => $kuantitas,
            'subtotal_rupiah' => $harga * $kuantitas,
        ];
    }
}
