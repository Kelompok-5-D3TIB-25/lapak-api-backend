<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TokoModerasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'slug' => $this->slug,
            'kota' => $this->kota,
            'status' => $this->status->value,
            'pemilik' => $this->whenLoaded('pemilik', fn () => [
                'id' => $this->pemilik->id,
                'nama' => $this->pemilik->nama,
                'surel' => $this->pemilik->surel,
            ]),
            'jumlah_produk' => $this->whenCounted('produk'),
            'catatan_moderasi' => $this->catatan_moderasi,
            'dimoderasi_pada' => $this->dimoderasi_pada?->toIso8601String(),
        ];
    }
}
