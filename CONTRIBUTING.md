# Panduan Kontribusi

## Alur kerja

Setiap sesi punya empat unggahan:

| Unggahan | Isi | Aktor |
|---|---|---|
| Pembuka | Papan sesi, pembaruan CODEOWNERS, fondasi (Sesi 1) | Integrator |
| A. Kontrak | Migration, enum, model polos, antarmuka | Setiap anggota |
| B. Implementasi | Logika, endpoint, seeder, uji | Setiap anggota |
| Penutup | Papan sesi terisi, tempat penegakan aturan, tag | Integrator |

Kontrak harus kecil dan dapat di-merge dalam hitungan menit. Logika masuk jendela B.

## Cabang

```bash
git switch main
git pull origin main
git switch -c sesi-<n>/<jenis>-<nama>
```

Contoh: `sesi-1/kontrak-uang-mirda`, `sesi-1/belanja-mirda`, `sesi-1/penutup-dipta`.

## Sebelum meminta tinjauan

```bash
git fetch origin
git rebase origin/main
./vendor/bin/pint --test
php artisan migrate:fresh --seed
./vendor/bin/pest
git push --force-with-lease
```

Bila push ditolak pada cabang bersama, gunakan `git pull --rebase`, bukan `--force`. `--force-with-lease` hanya untuk cabang milik sendiri setelah rebase.

## Berkas bersama

| Berkas | Aturan |
|---|---|
| `src/routes/api.php` | Tidak disunting setelah fondasi. Rute ditulis di `src/routes/api/<domain>.php`. |
| `src/database/seeders/DatabaseSeeder.php` | Tambah satu baris; urutan mengikuti arah foreign key. |
| `src/bootstrap/app.php`, `src/app/Providers/AppServiceProvider.php` | Hanya pemilik D5 atau atas tinjauannya. |
| `.github/`, `src/config/lapak.php`, `src/composer.json` | Wajib ditinjau pemilik D5. |
| `.github/CODEOWNERS`, `docs/sesi/`, `docs/ATURAN-BISNIS.md` | Hanya integrator sesi berjalan. |
| `docs/log-ai/<nama>.md` | Satu berkas per anggota, tidak pernah bertabrakan. |

## Penomoran migration

Pola: `2026_10_SS_DNNNNN_nama.php`

- `SS` = nomor sesi (01)
- `D` = nomor domain (1–5)
- `NNNNN` = nomor urut

Contoh: `2026_10_01_200003_buat_tabel_produk.php` adalah migration ketiga D2 pada Sesi 1.

## Pesan commit

Format: `<jenis>: <ringkasan>`

Jenis yang dipakai: `feat`, `fix`, `test`, `docs`, `ci`, `style`, `chore`.

Contoh:

```text
feat: endpoint registrasi pengguna
test: uji katalog publik (LP-4)
docs: peta skema dan piagam domain belanja
```

## Pull request

Isi template PR:

- Jenis unggahan
- Ringkasan
- Aturan bisnis yang tersentuh (`LP-...`)
- Berkas bersama yang disentuh
- Cara menguji
- Catatan penggunaan AI (tercatat di `docs/log-ai/<nama>.md`)
- Daftar periksa

Aturan:

- Satu PR satu tujuan.
- Jangan merge PR sendiri.
- Reviewer menjalankan cabang dan menuliskan hasilnya.
- Jangan commit `.env`, `vendor/`, atau rahasia apa pun.

## Catatan penggunaan AI

Setiap anggota mengisi `docs/log-ai/<nama>.md`: bagian yang dibantu AI, alatnya, dan cara memverifikasinya. Bila tidak memakai AI, tulis "tidak memakai AI pada sesi ini".
