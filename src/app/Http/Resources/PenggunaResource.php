<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pengguna
 */
final class PenggunaResource extends JsonResource
{
    /** Daftar putih: hanya kolom yang ditulis di sini yang keluar dari server. */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'surel' => $this->surel,
            'telepon' => $this->telepon,
            'status' => $this->status->value,
            // Hanya ikut bila relasinya sudah dimuat pemanggil. Resource tidak memicu kueri sendiri.
            'peran' => $this->whenLoaded(
                'peran',
                fn () => $this->peran->pluck('nama')->sort()->values()->all(),
            ),
            'terdaftar_pada' => $this->created_at?->toIso8601String(),
        ];
    }
}
