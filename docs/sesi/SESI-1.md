# Sesi 1 — Fondasi Tim dan Repositori

- Integrator: @ihsanpradipta45-alt
- Tag penutup: `sesi-1`
- Status: selesai

## Papan unggahan

| PR | Unggahan | Cabang | Penulis | Peninjau | Status |
|---|---|---|---|---|---|
| #1 | Pembuka | `sesi-1/fondasi` | Kelimanya | 2 anggota | Selesai |
| #2 | A1 | `sesi-1/kontrak-respons-lintar` | @Rokkett2500 | @ihsanpradipta45-alt | Selesai |
| #3 | A2 | `sesi-1/kontrak-uang-mirda` | @mrdaknsh | @nabelazahra-ptr26 | Selesai |
| #4 | A3 | `sesi-1/kontrak-identitas-dipta` | @ihsanpradipta45-alt | @victor-isMe | Selesai |
| #5 | A4 | `sesi-1/kontrak-katalog-faiz` | @victor-isMe | @mrdaknsh | Selesai |
| #6 | A5 | `sesi-1/kontrak-dompet-bela` | @nabelazahra-ptr26 | @Rokkett2500 | Selesai |
| #7 | B1 | `sesi-1/galat-dan-ci-lintar` | @Rokkett2500 | @ihsanpradipta45-alt | Selesai |
| #8 | B2 | `sesi-1/registrasi-dipta` | @ihsanpradipta45-alt | @victor-isMe | Selesai |
| #9 | B3 | `sesi-1/katalog-faiz` | @victor-isMe | @mrdaknsh | Selesai |
| #10 | B4 | `sesi-1/belanja-mirda` | @mrdaknsh | @nabelazahra-ptr26 | Selesai |
| #11 | B5 | `sesi-1/buku-besar-bela` | @nabelazahra-ptr26 | @Rokkett2500 | Selesai |
| #12 | Penutup | `sesi-1/penutup-dipta` | @ihsanpradipta45-alt | @victor-isMe | Selesai |

## Catatan penutup

### Kendala

- Penanganan urutan seeder pada `DatabaseSeeder.php` saat penggabungan seeder katalog dan dompet diselesaikan dengan pemanggilan sekuensial yang konsisten (`PenggunaSeeder` -> `KatalogSeeder` -> `DompetSeeder`).
- Workflow GitHub Actions memerlukan penyesuaian dependensi `pdo_sqlite` dan direktori kerja agar pengujian berjalan mulus pada lingkungan CI tanpa kontainer Docker.

### Titipan untuk integrator Sesi 2

- Integrator Sesi 2 diserahterimakan kepada Bagas (@victor-isMe).
- Siapkan berkas papan sesi `docs/sesi/SESI-2.md` sebelum memulai sesi berikutnya.
- Sesi 2 akan memfokuskan implementasi otentikasi kunci sesi (`lpk_<ulid>.<hex>`), middleware `auth:sesi`, login, logout, dan manajemen katalog penjual.
- Seluruh anggota tim diimbau menuntaskan latihan mandiri 1 sampai 6 sebelum sesi 2 dimulai.
