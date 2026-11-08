<?php

declare(strict_types=1);

namespace App\Domain\Belanja;

use App\Domain\Uang;

/**
 * Hasil hitung untuk satu toko. Kelak menjadi satu pesanan (LP-7).
 */
final readonly class RincianToko
{
    /**
     * @param  list<BarisBelanja>  $baris
     */
    public function __construct(
        public int $tokoId,
        public string $namaToko,
        public array $baris,
        public Uang $subtotal,
        public Uang $ongkir,
        public Uang $komisi,
    ) {}

    /** Yang dibayar pembeli untuk toko ini. */
    public function total(): Uang
    {
        return $this->subtotal->tambah($this->ongkir);
    }

    /** Yang kelak dilepas ke saldo penjual: komisi hanya memotong subtotal, ongkir utuh (LP-8). */
    public function bagianPenjual(): Uang
    {
        return $this->subtotal->kurang($this->komisi)->tambah($this->ongkir);
    }
}
