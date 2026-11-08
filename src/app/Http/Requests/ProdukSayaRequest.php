<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\StatusProduk;
use App\Services\PenjagaStok;
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
            // Stok hanya ditetapkan saat produk dibuat. Sesudahnya stok berubah lewat PenjagaStok:
            // angka mutlak dari layar penjual dapat menimpa penjualan yang terjadi setelah layar
            // itu dimuat (LP-4).
            'stok' => $this->isMethod('POST')
                ? ['required', 'integer', 'between:0,'.PenjagaStok::MAKSIMUM]
                : ['prohibited'],
            'berat_gram' => ['sometimes', 'integer', 'between:0,100000'],
            'status' => ['sometimes', Rule::enum(StatusProduk::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'stok.prohibited' => 'Ubah stok dengan selisih lewat POST /toko-saya/produk/{id}/stok.',
        ];
    }
}
