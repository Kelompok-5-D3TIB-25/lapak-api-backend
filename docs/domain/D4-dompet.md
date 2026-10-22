# D4 — Pembayaran dan Saldo

- Pemilik: @nabelazahra-ptr26
- Tabel: `dompet`, `mutasi_saldo`
- Aturan: LP-12, LP-13, LP-16

## Pertanyaan yang dijawab

Di mana setiap rupiah berada saat ini?

## Yang dibutuhkan dari domain lain

- `pengguna.id` dari D1 untuk dompet penjual.
- `App\Domain\Uang` dari D3 untuk nilai pemindahan.

## Yang disediakan untuk domain lain

- `App\Services\BukuBesar` — satu-satunya pintu perubahan saldo.
- `dompetSistem(JenisDompet)` dan `dompetPenjual(Pengguna)`.
- `pindahkan()` — mencatat sepasang mutasi berjumlah nol.
- `php artisan lapak:periksa-buku` — memeriksa empat invarian LP-13.

## Kontrak Sesi 1

Belum ada endpoint HTTP. Pintu HTTP ke uang baru dibuat setelah identitas (Sesi 2) dan otorisasi (Sesi 3) tersedia.

## Aturan

- Saldo hanya berubah lewat pemindahan antardompet (LP-13).
- Setiap pemindahan menghasilkan tepat dua baris `mutasi_saldo` dengan jumlah nol.
- Mutasi tidak boleh diubah atau dihapus.
- Hanya dompet `eksternal` yang boleh bersaldo negatif.
- Pemindahan nol rupiah dan pemindahan ke dompet yang sama ditolak.
- Baris dikunci urut `id` menaik untuk mencegah deadlock.
- `lapak:periksa-buku` dijalankan pada gerbang CI.

## Batas domain

D4 tidak menghitung harga belanja dan tidak menentukan status pesanan; itu milik D3 dan sesi berikutnya.
