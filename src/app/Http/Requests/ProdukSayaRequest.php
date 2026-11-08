<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\StatusProduk;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Dipakai menambah produk (POST) dan mengubahnya (PATCH).
 * Tidak ada toko_id: produk selalu masuk ke toko milik pengguna yang sedang masuk.
 */
final class ProdukSayaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $wajib = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'nama' => [$wajib, 'string', 'min:3', 'max:120'],
            'kategori_id' => [$wajib, 'integer', Rule::exists('kategori', 'id')],
            'deskripsi' => ['sometimes', 'nullable', 'string', 'max:2000'],
            // LP-1: aturan integer menolak 185000.5 maupun "185.000".
            // Batas atas mencegah luapan bilangan saat harga dikali kuantitas.
            'harga_rupiah' => [$wajib, 'integer', 'between:100,1000000000'],
            'stok' => [$wajib, 'integer', 'between:0,100000'],
            'berat_gram' => ['sometimes', 'integer', 'between:0,100000'],
            'status' => ['sometimes', Rule::enum(StatusProduk::class)],
        ];
    }
}
