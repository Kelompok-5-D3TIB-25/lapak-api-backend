# D1 — Identitas dan Sesi

- Pemilik: @ihsanpradipta45-alt
- Tabel: `pengguna`
- Aturan: LP-14, LP-15 (Sesi 2)

## Pertanyaan yang dijawab

Siapa yang sedang berbicara dengan sistem, dan apa buktinya?

## Yang dibutuhkan dari domain lain

Tidak ada. D1 adalah fondasi; domain lain merujuk `pengguna.id`.

## Yang disediakan untuk domain lain

- `App\Models\Pengguna` — `id`, `nama`, `surel`, `status`.
- `config/auth.php` — penyedia autentikasi `pengguna`.
- Status akun: `aktif`, `ditangguhkan`.

## Kontrak Sesi 1

- `POST /api/v1/registrasi`
- `status` tidak dapat diisi klien; selalu `aktif` saat registrasi.
- `kata_sandi` disimpan sebagai hash dan tidak pernah keluar pada respons.
- `surel` dinormalkan ke huruf kecil sebelum pemeriksaan keunikan.
- `telepon` opsional.

## Batas domain

D1 tidak menyimpan toko, keranjang, atau saldo. Tabel itu milik D2, D3, dan D4.
