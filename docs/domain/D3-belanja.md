# D3 — Keranjang dan Pesanan

- Pemilik: @mrdaknsh
- Tabel: `keranjang`, `butir_keranjang`
- Aturan: LP-1, LP-4, LP-5, LP-6, LP-7, LP-8

## Pertanyaan yang dijawab

Berapa yang harus dibayar, dan pesanan mana yang sah?

## Yang dibutuhkan dari domain lain

- `pengguna.id` dari D1 untuk `keranjang.pengguna_id`.
- `Produk::query()->tampil()` dan `toko.ongkir_rupiah` dari D2.

## Yang disediakan untuk domain lain

- `App\Domain\Uang` — value object uang bulat (dipakai D2 dan D4).
- `App\Domain\Belanja\PenghitungBelanja` — subtotal, ongkir, komisi, total.
- `App\Services\PenyusunBelanja` — menyusun rincian dari `produk_id` dan `kuantitas`.
- `RincianToko` — satu rincian per toko.

## Kontrak Sesi 1

- `POST /api/v1/simulasi-belanja`

```json
{
  "butir": [
    { "produk_id": 2, "kuantitas": 2 },
    { "produk_id": 5, "kuantitas": 3 }
  ]
}
```

## Aturan

- Harga tidak disimpan di keranjang; selalu dibaca dari `produk` saat dihitung (LP-6).
- Satu toko menghasilkan satu rincian (LP-7).
- Komisi 5% dari subtotal, dibulatkan setengah ke atas; ongkir utuh untuk penjual (LP-8).
- Produk tersembunyi dan produk tidak ada dijawab dengan pesan yang sama agar tidak membocorkan keberadaan produk (LP-4).
- Simulasi tidak mengunci harga maupun stok; kepastian baru ada saat checkout.

## Batas domain

D3 tidak memindahkan saldo. Perpindahan uang adalah D4 lewat `BukuBesar`.
