# Sesi 4 — Keamanan Transaksi

- Integrator: @nabelazahra-ptr26
- Tag penutup: `sesi-4`
- Status: selesai

## Papan unggahan

| PR | Unggahan | Cabang | Penulis | Peninjau | Status |
|---|---|---|---|---|---|
| #36 | Pembuka | `sesi-4/pembuka-bela` | @nabelazahra-ptr26 | @mrdaknsh | Selesai |
| #37 | A1 | `sesi-4/kontrak-idempoten-sekar` | @Rokkett2500 | @nabelazahra-ptr26 | Selesai |
| #38 | A2 | `sesi-4/kontrak-alamat-dipta` | @ihsanpradipta45-alt | @Rokkett2500 | Selesai |
| #39 | A3 | `sesi-4/kontrak-stok-bagas` | @victor-isMe | @Pradipta-ihsan-ariyanto | Selesai |
| #40 | A4 | `sesi-4/kontrak-pesanan-rani` | @mrdaknsh | @victor-isMe | Selesai |
| #41 | A5 | `sesi-4/kontrak-pembayaran-dimas` | @nabelazahra-ptr26 | @mrdaknsh | Selesai |
| #42 | B1 | `sesi-4/alamat-dipta` | @ihsanpradipta45-alt | @Rokkett2500 | Selesai |
| #43 | B2 | `sesi-4/checkout-rani` | @mrdaknsh | @victor-isMe | Selesai |
| #44 | B3 | `sesi-4/kunci-stok-bagas` | @victor-isMe | @Pradipta-ihsan-ariyanto | Selesai |
| #45 | B4 | `sesi-4/webhook-dimas` | @nabelazahra-ptr26 | @mrdaknsh | Selesai |
| #46 | B5 | `sesi-4/balapan-ci-sekar` | @Rokkett2500 | @nabelazahra-ptr26 | Selesai |
| #48 | P1 | `sesi-4/urutan-kunci-rani` | @mrdaknsh | @victor-isMe | Selesai |
| #49 | Penutup | `sesi-4/penutup-bela` | @nabelazahra-ptr26 | @Rokkett2500 | Selesai |

P1 (`sesi-4/urutan-kunci-rani`) di-merge sebelum B5 walau nomornya lebih besar: urutan dependency lebih penting daripada nomor PR. Koreksi SESI-3 terpisah di PR #35 (menunggu review).

## Sasaran sesi

- Checkout sebagai SATU transaksi basis data: stok, pesanan, tagihan, keranjang, semua atau tidak sama sekali (LP-4, LP-6, LP-7, LP-10, LP-11).
- Kunci baris (`lockForUpdate()`) menggantikan baca-putuskan-tulis tanpa kunci; isolasi `REPEATABLE READ` bawaan MySQL dihormati dengan aturan *kunci dulu, baca kemudian*.
- Urutan kunci disepakati tim dan ditulis di `docs/PETA-SKEMA.md` (bagian "Urutan kunci baris").
- Idempotensi (`Idempotency-Key`) dicatat dalam transaksi yang sama dengan efeknya (LP-11).
- Webhook pembayaran bertanda tangan HMAC + gembok checkout; uang masuk escrow tepat satu kali (LP-12).
- Uji balapan pada MySQL sungguhan dan tugas CI `kualitas-mysql`.

## Tangga kunci (urutan kunci baris)

| Urut | Baris | Dikunci oleh |
|---|---|---|
| 1 | `pengguna`, lalu `alamat` miliknya | `BukuAlamat` |
| 2 | `keranjang`, lalu `butir_keranjang` | `LayananCheckout` |
| 3 | `checkout` | `GembokCheckout` |
| 4 | `pembayaran` | `LayananPembayaran` |
| 5 | `pesanan` | `MesinStatusPesanan` |
| 6 | `produk`, urut nomor menaik | `PenjagaStok` |
| 7 | `dompet`, urut nomor menaik | `BukuBesar` |

Aturan menyertai: (1) kunci hanya diambil di dalam `DB::transaction()`; (2) kunci dulu, baca kemudian — data dasar keputusan dibaca dengan `lockForUpdate()` setelah kunci induknya didapat; (3) tidak ada panggilan layanan luar atau pekerjaan lama di dalam transaksi. Middleware `idempoten` mencatat kuncinya sebelum anak tangga pertama.

## Jawaban titipan Sesi 3

1. **AturanTransisi sudah teruji tetapi belum dipakai** → dipakai `MesinStatusPesanan` (A4) di bawah `GembokCheckout`; dipanggil checkout, webhook, dan pembatalan otomatis.
2. **Pesanan milik orang lain dijawab 404** → `Pesanan::scopeMilikPembeli()` pada seluruh kueri pintu pembeli; nomor pesanan ditebak pun dijawab 404 (B2).
3. **UPDATE bersyarat belum cukup untuk stok dan saldo** → `PenjagaStok::ambil()` mengunci baris `produk` urut nomor (B3); saldo tetap satu-satunya lewat `BukuBesar`; penyesuaian stok penjual memakai selisih relatif + `Idempotency-Key` (B3).
4. **Persetujuan toko dan perubahan peran belum masuk log audit** → belum tersentuh Sesi 4 (tidak ada domain audit pada sesi ini); dititipkan ke Sesi 5 bersama pelunasan/pembatalan otomatis yang belum masuk log audit.

## Tujuh keputusan rancangan

1. Checkout adalah satu transaksi; galat di baris mana pun membatalkan seluruhnya (harga yang tidak terpakai tidak pernah ada).
2. Baris `checkout` adalah induk bersama dan sekaligus gembok (`GembokCheckout`); webhook dan pembatalan otomatis antre di sana, tidak saling mendahului.
3. Urutan kunci tujuh anak tangga ditulis sebelum ada yang membuka cabang; transaksi boleh melompati anak tangga tetapi tidak pernah naik kembali.
4. Catatan `Idempotency-Key` dan efeknya berada dalam satu transaksi; rute yang tidak idempoten tercatat di `RuteIdempotenTest` beserta alasannya.
5. Nama, harga, dan alamat pada pesanan adalah salinan (snapshot), bukan penunjuk; riwayat tidak berubah mengikuti katalog (LP-6, LP-7).
6. Satu-satunya pintu "lunas" adalah webhook bertanda tangan HMAC; jumlah dicocokkan dengan tagihan; kabar kembar aman diulang di bawah gembok (LP-12).
7. Kontrak boleh belum aman asal tertulis: `PenjagaStok` A3 sengaja belum mengunci dan komentarnya berkata "BELUM AMAN" — lubangnya ditutup B3.

## Urutan merge

`pembuka` → `A1` `A2` `A3` `A4` (saling bebas) → `A5` (menumpang cabang A4, di-merge setelah A4 masuk) → `B1` `B2` `B3` `B4` → `P1` (setelah temuan B5) → `B5` → `penutup`.

## Catatan kendala

- `main` merah saat Sesi 4 dimulai (38 uji tanpa peran; klaim "269 uji" di SESI-3.md tidak pernah benar; Sesi 3 semula masuk lewat push langsung lalu ditulis ulang menjadi PR #26–#34 yang di-merge dalam keadaan CI merah tanpa review). Diperbaiki di PR #25 (ditutup tanpa merge, isinya diserap `main` saat penutupan ulang Sesi 3) dan PR #35 (koreksi dokumen, menunggu review). CI `main` hijau sejak #34.
- Jumlah uji aktual saat Sesi 4 dibuka: 193 (723 asersi), bukan 269.
- `lapak_uji` dan hak user `lapak` dibuat ulang sebagai persiapan pengujian MySQL.
- Mesin pengembangan ini tidak sanggup melepas 8 proses serempak dalam jendela 250 ms (kalibrasi `serempak` gagal pada 8, lulus pada 6 setelah garis mulai dinaikkan menjadi `max(4.0, jumlah * 0.5)` detik). Skenario lain tidak terpengaruh karena anak-anak menunggu garis mulai yang sama.
- Review PR #40 sempat tercatat atas nama Rokkett2500 alih-alih victor-isMe (shell yang menulis masih memegang sesi tinjau sebelumnya); Bagas menyetujui setelah perbaikan dan itu dicatat di PR #40.
- GitHub hanya mengenal 24 PR sebelum Sesi 4 (seluruh PR #26–#34 Sesi 3 adalah rewrite yang dibuat ulang), sehingga penomoran PR Sesi 4 bergeser: pembuka #36 … penutup #49, bukan #34–#46 seperti pada dokumen awal.

## Catatan penutup

Sesi 4 selesai dengan 292 uji (1121 asersi) pada SQLite maupun MySQL, 9 skenario balapan lulus, `lapak:periksa-buku` seimbang (6 mutasi), 43 rute, Pint lolos, dan `kualitas-mysql` menjadi pemeriksaan wajib pada `main`.

**Titipan untuk Lintar (Integrator Sesi 5):**

1. Pembayaran yang tiba setelah tagihan kedaluwarsa masih **ditolak 409** (LP-10) padahal uangnya sudah diterima gerbang; pengembalian dananya adalah pekerjaan Sesi 5.
2. Pintu penjual: konfirmasi terima, pembatalan oleh pembeli, dan pintu audit (persetujuan toko, perubahan peran, pelunasan, pembatalan otomatis) belum ada log auditnya — menyusul Sesi 5.
3. Setiap rute POST baru **wajib** masuk `RuteIdempotenTest` (salah satu dari dua daftar); rute tulis tanpa kunci sesi menuntut tinjauan pemilik D5.
4. Catatan `kunci_idempotensi` belum pernah dipangkas; pertimbangkan `lapak:pangkas-idempotensi` (Latihan 6 Sesi 4).
5. Kerangka papan sesi sudah disalin menjadi `docs/sesi/SESI-5.md`; isi sasaran Sesi 5 sebelum dimulai.
6. `LAPAK_GATEWAY_RAHASIA` pada `.env.example` (`rahasia-gerbang-untuk-latihan`) hanya untuk mesin pengembangan; di produksi, rahasia berasal dari gerbang dan tidak pernah masuk repositori.
7. `kualitas-mysql` kini pemeriksaan wajib di samping `kualitas`; setiap PR harus hijau pada keduanya sebelum di-merge.

**Temuan sesi ini (P1 / PR #48):** skenario lintas domain `batal-saat-checkout` menemukan kebuntuan (SQLSTATE 1213) antara pembelian (kunci `produk` urut nomor) dan pembatalan otomatis (pengembalian stok menurut urutan butir). Diperbaiki pemilik domain D3: seluruh pesanan ditandai lebih dulu, kuantitas dijumlah per produk, dan stok dikembalikan urut nomor menaik. Dibuktikan merah di sini dan hijau sesudah perbaikan; `RuteIdempotenTest` menjaga agar rute POST baru tetap terklasifikasi.

## Rekap kontribusi

```
== Commit per anggota (sesi-3..sesi-4, tanpa merge commit)
     9	belazahra
     7	Rokkett2500
     7	mrdaknsh
     6	Pradipta-ihsan-ariyanto
     5	victor-isMe

== Baris ditambah dan dihapus per anggota
belazahra    +1231 -31
mrdaknsh     +1576 -14
Pradipta-ihsan-ariyanto +730 -6
Rokkett2500  +1208 -8
victor-isMe  +586 -4

== PR yang masuk ke main
   Merge pull request #49 from Kelompok-5-D3TIB-25/sesi-4/penutup-bela
   Merge pull request #46 from Kelompok-5-D3TIB-25/sesi-4/balapan-ci-sekar
   Merge pull request #48 from Kelompok-5-D3TIB-25/sesi-4/urutan-kunci-rani
   Merge pull request #45 from Kelompok-5-D3TIB-25/sesi-4/webhook-dimas
   Merge pull request #44 from Kelompok-5-D3TIB-25/sesi-4/kunci-stok-bagas
   Merge pull request #43 from Kelompok-5-D3TIB-25/sesi-4/checkout-rani
   Merge pull request #42 from Kelompok-5-D3TIB-25/sesi-4/alamat-dipta
   Merge pull request #41 from Kelompok-5-D3TIB-25/sesi-4/kontrak-pembayaran-dimas
   Merge pull request #40 from Kelompok-5-D3TIB-25/sesi-4/kontrak-pesanan-rani
   Merge pull request #39 from Kelompok-5-D3TIB-25/sesi-4/kontrak-stok-bagas
   Merge pull request #38 from Kelompok-5-D3TIB-25/sesi-4/kontrak-alamat-dipta
   Merge pull request #37 from Kelompok-5-D3TIB-25/sesi-4/kontrak-idempoten-sekar
   Merge pull request #36 from Kelompok-5-D3TIB-25/sesi-4/pembuka-bela
```
