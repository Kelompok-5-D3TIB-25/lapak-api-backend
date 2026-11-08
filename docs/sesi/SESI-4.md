# Sesi 4 — Keamanan Transaksi

- Integrator: @nabelazahra-ptr26
- Tag penutup: `sesi-4`
- Status: berjalan

## Papan unggahan

| PR | Unggahan | Cabang | Penulis | Peninjau | Status |
|---|---|---|---|---|---|
| #36 | Pembuka | `sesi-4/pembuka-bela` | @nabelazahra-ptr26 | @mrdaknsh | Draf |
| #37 | A1 | `sesi-4/kontrak-idempoten-sekar` | @Rokkett2500 | @nabelazahra-ptr26 | Menunggu |
| #38 | A2 | `sesi-4/kontrak-alamat-dipta` | @ihsanpradipta45-alt | @Rokkett2500 | Menunggu |
| #39 | A3 | `sesi-4/kontrak-stok-bagas` | @victor-isMe | @Pradipta-ihsan-ariyanto | Menunggu |
| #40 | A4 | `sesi-4/kontrak-pesanan-rani` | @mrdaknsh | @victor-isMe | Menunggu |
| #41 | A5 | `sesi-4/kontrak-pembayaran-dimas` | @nabelazahra-ptr26 | @mrdaknsh | Menunggu |
| #42 | B1 | `sesi-4/alamat-dipta` | @ihsanpradipta45-alt | @Rokkett2500 | Menunggu |
| #43 | B2 | `sesi-4/checkout-rani` | @mrdaknsh | @victor-isMe | Menunggu |
| #44 | B3 | `sesi-4/kunci-stok-bagas` | @victor-isMe | @Pradipta-ihsan-ariyanto | Menunggu |
| #45 | B4 | `sesi-4/webhook-dimas` | @nabelazahra-ptr26 | @mrdaknsh | Menunggu |
| #46 | B5 | `sesi-4/balapan-ci-sekar` | @Rokkett2500 | @nabelazahra-ptr26 | Menunggu |
| #47 | P1 | `sesi-4/urutan-kunci-rani` | @mrdaknsh | @victor-isMe | Menunggu (temuan B5) |
| #48 | Penutup | `sesi-4/penutup-bela` | @nabelazahra-ptr26 | @Rokkett2500 | Menunggu |

Nomor PR adalah perkiraan; GitHub memberi nomor saat PR dibuka. P1 (`sesi-4/urutan-kunci-rani`) di-merge sebelum B5 walau dibuka setelahnya: urutan dependency lebih penting daripada nomor PR.

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
