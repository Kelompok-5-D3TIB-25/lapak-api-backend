<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ButirKeranjangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Hanya kuantitas. Produk disebut di alamat, harga tidak pernah diterima dari klien (LP-6). */
    public function rules(): array
    {
        $maksimum = config('lapak.belanja.kuantitas_maksimum');

        return ['kuantitas' => ['required', 'integer', 'between:1,'.$maksimum]];
    }
}
