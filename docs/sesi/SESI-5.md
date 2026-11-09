# Sesi 5 — Escrow, Saldo, Audit, dan Rilis

- Integrator: @Rokkett2500
- Tag penutup: `sesi-5`
- Status: kerangka (belum dimulai)

## Papan unggahan

| PR | Unggahan | Cabang | Penulis | Peninjau | Status |
|---|---|---|---|---|---|
| - | Pembuka | `sesi-5/pembuka-sekar` | | | |

## Sasaran sesi

- Pelepasan dana dari escrow ke saldo penjual (bagian dari LP-12), dipotong komisi (LP-8).
- Log audit hanya-tambah dan berantai hash (LP-17).
- Pencairan saldo dengan persetujuan keuangan (LP-16).
- Pintu penjual: konfirmasi terima, pembatalan oleh pembeli (bagian LP-10).

## Titipan dari Sesi 4

1. Pengembalian dana atas pembayaran yang tiba setelah tagihan kedaluwarsa (ditolak 409, uang sudah diterima gerbang).
2. Pintu audit untuk persetujuan toko, perubahan peran, pelunasan, dan pembatalan otomatis.
3. `lapak:pangkas-idempotensi` (penghapusan catatan kunci idempotensi berumur lebih dari 7 hari).
4. Setiap rute POST baru wajib masuk `RuteIdempotenTest`.
5. `kualitas-mysql` sudah wajib pada `main`; jaga setiap PR tetap hijau pada `kualitas` dan `kualitas-mysql`.
