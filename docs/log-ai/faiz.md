# Catatan Penggunaan AI — Faiz

## Sesi 1

| Tanggal | Bagian | Alat | Cara verifikasi |
|---|---|---|---|
| 2026-10-22 | Sintaks scope Eloquent dan pencarian SQL LIKE | Asisten AI | ./vendor/bin/pest tests/Feature/Katalog/ |

## Catatan

- Bagian yang dibantu AI: Sintaks escaping wildcard LIKE SQL dan format paginasi resource.
- Bagian yang ditulis sendiri: Migration tabel toko, kategori, produk, scopeTampil, dan seeder katalog Solo Raya.
- Hal yang perlu diperbaiki setelah keluaran AI: Memastikan operator escape query LIKE aman dari injeksi karakter khusus.
