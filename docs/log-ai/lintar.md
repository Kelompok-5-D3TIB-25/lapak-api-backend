# Catatan Penggunaan AI — Lintar

## Sesi 1

| Tanggal | Bagian | Alat | Cara verifikasi |
|---|---|---|---|
| 2026-10-22 | Pola regex pengecekan migration integer rupiah pada ArchitectureTest | Asisten AI | ./vendor/bin/pest tests/Feature/Sistem/ |

## Catatan

- Bagian yang dibantu AI: Penulisan ekspresi reguler untuk regex scanner pada file migration.
- Bagian yang ditulis sendiri: Seluruh trait `ResponsApi`, kelas `GalatDomain`, `PenanganGalatApi`, middleware `PaksaJson` dan `IdPermintaan`, workflow CI GitHub Actions, dan pengujian Pest.
- Hal yang perlu diperbaiki setelah keluaran AI: Memastikan penangan galat API menangani AuthenticationException dan ValidationException dengan status HTTP yang presisi.

## Sesi 3

| Tanggal | Bagian | Alat | Cara verifikasi |
|---|---|---|---|
| 2026-11-15 | RBAC, peran, izin, middleware, matriks | Asisten AI | php artisan test, pint --test |

## Sesi 4

| Tanggal | Bagian | Alat | Cara verifikasi |
|---|---|---|---|
| 2026-11-09 | A1 kunci idempotensi dan alat lapak:balapan; B5 klasifikasi rute POST, skenario lintas domain, tugas CI kualitas-mysql | Asisten AI | php artisan lapak:balapan --semua pada MySQL, pest pada SQLite dan MySQL, pint --test, CI kualitas dan kualitas-mysql hijau |
