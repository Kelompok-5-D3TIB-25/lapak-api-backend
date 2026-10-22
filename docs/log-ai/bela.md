# Catatan Penggunaan AI — Bela

## Sesi 1

| Tanggal | Bagian | Alat | Cara verifikasi |
|---|---|---|---|
| 2026-10-22 | Perintah Artisan command PeriksaBuku output tabel | Asisten AI | php artisan lapak:periksa-buku |

## Catatan

- Bagian yang dibantu AI: Sintaks formatting output tabel command Artisan Symfony Console.
- Bagian yang ditulis sendiri: Seluruh skema dompet, mutasi saldo, BukuBesar double-entry bookkeeping, deadlock prevention (sort id), dan tes Pest.
- Hal yang perlu diperbaiki setelah keluaran AI: Memastikan lockForUpdate hanya dijalankan di dalam DB::transaction.
