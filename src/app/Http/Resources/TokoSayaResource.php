<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Uang;
use App\Enums\StatusToko;
use App\Models\Toko;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Toko seperti yang dilihat PEMILIKNYA. Berbeda dari TokoResource publik:
 * pemilik boleh melihat status tokonya, pengunjung tidak.
 *
 * @mixin Toko
 */
final class TokoSayaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'nama' => $this->nama,
            'slug' => $this->slug,
            'kota' => $this->kota,
            'deskripsi' => $this->deskripsi,
            'ongkir' => [
                'rupiah' => $this->ongkir_rupiah,
                'tampilan' => Uang::dari($this->ongkir_rupiah)->format(),
            ],
            'status' => $this->status->value,
            'terlihat_publik' => $this->status === StatusToko::Aktif,
            'jumlah_produk' => $this->whenCounted('produk'),
        ];
    }
}
