# Catatan Penggunaan AI — Mirda

## Sesi 1

| Tanggal | Bagian | Alat | Cara verifikasi |
|---|---|---|---|
| 2026-10-22 | Rumus pembulatan setengah ke atas intdiv pada Uang | Asisten AI | ./vendor/bin/pest tests/Unit/UangTest.php |

## Catatan

- Bagian yang dibantu AI: Pengecekan algoritma pembulatan komisi integer rupiah tanpa floating point.
- Bagian yang ditulis sendiri: Seluruh class `Uang`, `BarisBelanja`, `RincianToko`, `PenghitungBelanja`, dan endpoint simulasi belanja.
- Hal yang perlu diperbaiki setelah keluaran AI: Memastikan seluruh metode Uang bersifat immutable dan mengembalikan instans baru.

## Sesi 3

| Tanggal | Bagian | Alat | Cara verifikasi |
|---|---|---|---|
| 2026-11-15 | RBAC, peran, izin, middleware, matriks | Asisten AI | php artisan test, pint --test |

## Sesi 4

| Tanggal | Bagian | Alat | Cara verifikasi |
|---|---|---|---|
| 2026-11-09 | A4 tabel checkout/pesanan, gembok checkout, mesin status; B2 checkout atomik dan pembatalan otomatis; P1 urutan kunci | Asisten AI | pest pada SQLite dan MySQL, skenario keranjang-ganda dan batal-saat-checkout pada MySQL |
