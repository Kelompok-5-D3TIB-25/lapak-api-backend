# Sesi 2 — Identitas dan Session dengan Kunci

- Integrator: @victor-isMe
- Tag penutup: `sesi-2`
- Status: berjalan

## Papan unggahan

| PR | Unggahan | Cabang | Penulis | Peninjau | Status |
|---|---|---|---|---|---|
| #13 | Pembuka | `sesi-2/pembuka-faiz` | @victor-isMe | @nabelazahra-ptr26 | - |
| #14 | A1 | `sesi-2/kontrak-sesi-dipta` | @ihsanpradipta45-alt | @mrdaknsh | - |
| #15 | A2 | `sesi-2/kontrak-guard-lintar` | @Rokkett2500 | @victor-isMe | - |
| #16 | A3 | `sesi-2/kontrak-toko-faiz` | @victor-isMe | @nabelazahra-ptr26 | - |
| #17 | A4 | `sesi-2/kontrak-keranjang-mirda` | @mrdaknsh | @Rokkett2500 | - |
| #18 | A5 | `sesi-2/kontrak-dompet-bela` | @nabelazahra-ptr26 | @ihsanpradipta45-alt | - |
| #19 | B1 | `sesi-2/masuk-dipta` | @ihsanpradipta45-alt | @mrdaknsh | - |
| #20 | B2 | `sesi-2/gerbang-lintar` | @Rokkett2500 | @victor-isMe | - |
| #21 | B3 | `sesi-2/toko-saya-faiz` | @victor-isMe | @nabelazahra-ptr26 | - |
| #22 | B4 | `sesi-2/keranjang-mirda` | @mrdaknsh | @Rokkett2500 | - |
| #23 | B5 | `sesi-2/mutasi-bela` | @nabelazahra-ptr26 | @ihsanpradipta45-alt | - |
| #24 | Penutup | `sesi-2/penutup-faiz` | @victor-isMe | @mrdaknsh | - |

## Sasaran sesi

Sesi ini membangun mekanisme autentikasi kunci sesi buatan sendiri:

- Format kunci: `lpk_<26-char ULID>.<64 hex>` — hanya hash SHA-256 yang disimpan di basis data
- Guard `sesi` menggantikan `sanctum`; setiap permintaan melewati `PemeriksaSesi`
- Sesi segar (`LP-15`): tindakan sensitif menuntut konfirmasi ulang kata sandi dalam N menit terakhir
- Pembatas laju pada `POST /masuk` dan `POST /sesi/konfirmasi`
- Kepemilikan data dari sesi: toko, produk, keranjang, dan dompet selalu milik pengguna yang sedang masuk
- Perintah `lapak:pangkas-sesi` membuang sesi yang sudah lama mati, dijadwalkan setiap dini hari

## Jawaban titipan Sesi 1

Sesi 1 memperkirakan "unggahan D5 selalu pertama" karena D5 biasanya menyiapkan infrastruktur bersama. Pada Sesi 2, urutan merge ditentukan ketergantungan: A1 (D1) lebih dulu karena D5 dan tiga domain lain bergantung pada `PenerbitSesi` dan `PemeriksaSesi`; A2 (D5) menunggu A1 masuk sebelum dapat di-rebase karena terjadi konflik di `src/config/lapak.php`. Urutan bukan berdasarkan domain, melainkan berdasarkan ketergantungan kode.

## Urutan merge Windows A

| Urutan | PR | Sebab |
|---|---|---|
| 1 | #14 A1 | Inti mekanisme; semua yang lain bergantung pada `PenerbitSesi` |
| 2 | #15 A2 | Menunggu A1; konflik `lapak.php` diselesaikan di sini |
| 3–5 | #16, #17, #18 | Menunggu #15; bebas urutan |

## Urutan merge Windows B

| Urutan | PR | Sebab |
|---|---|---|
| 1 | #19 B1 | `POST /masuk` dibutuhkan `RUTE_PUBLIK` B2 dan uji B5 |
| 2 | #20 B2 | Menunggu B1; gerbang tolak aktif setelah ini |
| 3–5 | #21, #22, #23 | Menunggu #19 dan #20; bebas urutan |

## Catatan penutup

*(diisi setelah PR #24 merged)*
