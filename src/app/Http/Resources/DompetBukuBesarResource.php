<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Uang;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DompetBukuBesarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode' => $this->kode,
            'jenis' => $this->jenis->value,
            'pemilik' => $this->whenLoaded('pemilik', fn () => $this->pemilik === null ? null : [
                'id' => $this->pemilik->id,
                'nama' => $this->pemilik->nama,
            ]),
            'saldo' => [
                'rupiah' => $this->saldo_rupiah,
                'tampilan' => ($this->saldo_rupiah < 0 ? '-' : '')
                    .Uang::dari(abs($this->saldo_rupiah))->format(),
            ],
        ];
    }
}
