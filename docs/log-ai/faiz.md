# Catatan Penggunaan AI — Faiz

## Sesi 1

| Tanggal | Bagian | Alat | Cara verifikasi |
|---|---|---|---|
| 2026-10-22 | Sintaks scope Eloquent dan pencarian SQL LIKE | Asisten AI | ./vendor/bin/pest tests/Feature/Katalog/ |

## Catatan

- Bagian yang dibantu AI: Sintaks escaping wildcard LIKE SQL dan format paginasi resource.
- Bagian yang ditulis sendiri: Migration tabel toko, kategori, produk, scopeTampil, dan seeder katalog Solo Raya.
- Hal yang perlu diperbaiki setelah keluaran AI: Memastikan operator escape query LIKE aman dari injeksi karakter khusus.

## Sesi 3

| Tanggal | Bagian | Alat | Cara verifikasi |
|---|---|---|---|
| 2026-11-15 | RBAC, peran, izin, middleware, matriks | Asisten AI | php artisan test, pint --test |

## Sesi 4

| Tanggal | Bagian | Alat | Cara verifikasi |
|---|---|---|---|
| 2026-11-09 | A3 penjaga stok versi kontrak, B3 kunci baris stok dan penyesuaian selisih (rebutan-stok, penyesuaian-stok) | Asisten AI | php artisan lapak:balapan rebutan-stok dan penyesuaian-stok pada MySQL, pest pada SQLite dan MySQL, pint --test |
