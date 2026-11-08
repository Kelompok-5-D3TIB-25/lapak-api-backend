# D2 — Toko dan Katalog

- Pemilik: @victor-isMe
- Tabel: `toko`, `kategori`, `produk`
- Aturan: LP-3, LP-4, LP-6

## Pertanyaan yang dijawab

Apa yang dijual, oleh siapa, dan mana yang boleh tampil?

## Yang dibutuhkan dari domain lain

- `pengguna.id` dari D1 untuk `toko.pemilik_id`.

## Yang disediakan untuk domain lain

- `Produk::query()->tampil()` — produk aktif dari toko aktif, stok > 0.
- `Toko` — `ongkir_rupiah` untuk perhitungan belanja.
- `Produk` — `harga_rupiah`, `stok`, `berat_gram`, `slug`.

## Kontrak Sesi 1

- `GET /api/v1/produk` — katalog berhalaman.
- `GET /api/v1/produk/{slug}` — detail produk tampil.
- `GET /api/v1/toko/{slug}` — profil toko aktif, tanpa membocorkan `pemilik_id`.

## Aturan

- Satu pengguna paling banyak satu toko (LP-3).
- Toko baru berstatus `menunggu` sampai disetujui admin.
- Produk hanya tampil bila `status = aktif`, toko `aktif`, dan `stok > 0` (LP-4).
- Harga selalu dari basis data; kiriman klien diabaikan (LP-6).
- Pencarian memperlakukan `%` dan `_` sebagai teks, bukan wildcard.

## Batas domain

D2 tidak menghitung total belanja dan tidak mengubah stok saat simulasi. Itu milik D3.
