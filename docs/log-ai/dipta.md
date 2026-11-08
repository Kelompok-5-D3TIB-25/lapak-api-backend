# Catatan Penggunaan AI — Dipta

## Sesi 1

| Tanggal | Bagian | Alat | Cara verifikasi |
|---|---|---|---|
| 2026-10-22 | Template FormRequest dan validasi regex telepon | Asisten AI | php artisan test tests/Feature/Identitas/ |

## Catatan

- Bagian yang dibantu AI: Referensi penulisan validasi form request di Laravel 11/12.
- Bagian yang ditulis sendiri: Seluruh migration pengguna, model Pengguna, controller registrasi, hashing password, dan test Pest.
- Hal yang perlu diperbaiki setelah keluaran AI: Menghapus dependensi yang tidak diperlukan dan memastikan model menggunakan nama kolom bahasa Indonesia (kata_sandi, surel).
