<?php

declare(strict_types=1);

namespace App\Domain\Belanja;

use App\Domain\Uang;
use InvalidArgumentException;

/**
 * Satu-satunya tempat rumus belanja ditulis. Simulasi (Sesi 1) dan checkout (Sesi 4)
 * memanggil kelas yang sama, sehingga angka yang dilihat pembeli sama dengan angka yang ditagih.
 */
final readonly class PenghitungBelanja
{
    public function __construct(private int $komisiPersen) {}

    /**
     * @param  list<BarisBelanja>  $baris
     * @param  array<int, array{nama: string, ongkir: Uang}>  $toko  dikunci id toko
     * @return list<RincianToko> satu rincian per toko, urut id toko
     */
    public function hitung(array $baris, array $toko): array
    {
        if ($baris === []) {
            throw new InvalidArgumentException('Belanja kosong tidak dapat dihitung.');
        }

        $perToko = [];
        foreach ($baris as $butir) {
            $perToko[$butir->tokoId][] = $butir;
        }
        ksort($perToko);

        $rincian = [];
        foreach ($perToko as $tokoId => $barisToko) {
            $subtotal = array_reduce(
                $barisToko,
                fn (Uang $jumlah, BarisBelanja $butir) => $jumlah->tambah($butir->subtotal()),
                Uang::nol(),
            );

            $rincian[] = new RincianToko(
                tokoId: $tokoId,
                namaToko: $toko[$tokoId]['nama'],
                baris: $barisToko,
                subtotal: $subtotal,
                ongkir: $toko[$tokoId]['ongkir'],
                komisi: $subtotal->persen($this->komisiPersen),
            );
        }

        return $rincian;
    }

    /**
     * @param  list<RincianToko>  $rincian
     */
    public static function totalBayar(array $rincian): Uang
    {
        return array_reduce(
            $rincian,
            fn (Uang $jumlah, RincianToko $toko) => $jumlah->tambah($toko->total()),
            Uang::nol(),
        );
    }
}
