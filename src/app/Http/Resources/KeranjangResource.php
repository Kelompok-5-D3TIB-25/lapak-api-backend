<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Belanja\ButirBermasalah;
use App\Domain\Belanja\PenghitungBelanja;
use App\Domain\Belanja\RincianToko;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk respons keranjang. Sumbernya adalah keluaran LayananKeranjang::lihat().
 *
 * @property-read array{0: list<RincianToko>, 1: list<ButirBermasalah>} $resource
 */
final class KeranjangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        [$rincian, $bermasalah] = $this->resource;
        $total = PenghitungBelanja::totalBayar($rincian);

        return [
            'pesanan' => RincianTokoResource::collection($rincian),
            'total_bayar' => ['rupiah' => $total, 'tampilan' => $total->format()],
            // Butir yang masih ada di keranjang tetapi tidak dapat dibeli saat ini.
            'tidak_tersedia' => array_map(fn (ButirBermasalah $butir) => [
                'produk_id' => $butir->produkId,
                'kuantitas' => $butir->kuantitas,
                'alasan' => $butir->alasan->value,
                'stok_tersedia' => $butir->stokTersedia,
            ], $bermasalah),
        ];
    }
}
