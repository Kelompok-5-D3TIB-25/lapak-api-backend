# Sesi 3 — Peran, Izin, dan Kewenangan atas Objek

- Integrator: @mrdaknsh
- Tag penutup: `sesi-3`
- Status: selesai

## Papan unggahan

| PR | Unggahan | Cabang | Penulis | Peninjau | Status |
|---|---|---|---|---|---|
| #25 | Pembuka | `sesi-3/pembuka-mirda` | @mrdaknsh | @ihsanpradipta45-alt | Selesai |
| #26 | A1 | `sesi-3/kontrak-izin-lintar` | @Rokkett2500 | @mrdaknsh | Selesai |
| #27 | A2 | `sesi-3/kontrak-peran-dipta` | @Pradipta-ihsan-ariyanto | @nabelazahra-ptr26 | Selesai |
| #28 | B1 | `sesi-3/peran-pengguna-dipta` | @Pradipta-ihsan-ariyanto | @nabelazahra-ptr26 | Selesai |
| #29 | B2 | `sesi-3/ingatan-izin-lintar` | @Rokkett2500 | @mrdaknsh | Selesai |
| #30 | B3 | `sesi-3/moderasi-toko-faiz` | @victor-isMe | @Rokkett2500 | Selesai |
| #31 | B4 | `sesi-3/gerbang-keranjang-mirda` | @mrdaknsh | @Pradipta-ihsan-ariyanto | Selesai |
| #32 | B5 | `sesi-3/buku-besar-bela` | @belazahra | @victor-isMe | Selesai |
| #33 | Penutup | `sesi-3/penutup-mirda` | @mrdaknsh | @nabelazahra-ptr26 | Selesai |

## Sasaran sesi

Sesi ini membangun RBAC berbasis izin:

- 5 peran: pembeli, penjual, admin, keuangan, auditor
- 7 izin: keranjang.kelola, toko.buka, produk.kelola, dompet.lihat, toko.moderasi, peran.kelola, buku-besar.lihat
- Middleware izin dengan prependToPriorityList sebelum SubstituteBindings
- PengelolaPeran: satu-satunya pintu perubahan peran, mencabut seluruh sesi
- PemeriksaIzin dengan cache ingatan izin
- Matriks otorisasi: satu baris per rute terlindung
- Moderasi toko oleh admin (setujui/tangguhkan)
- Aturan transisi status pesanan (PHP murni)
- Buku besar dapat diakses staf (keuangan, auditor)

## Jawaban titipan Sesi 2

Landasan identitas, session dengan kunci, toko, keranjang, dan dompet sudah kokoh dan teruji. Sesi 3 membangun RBAC di atasnya.

## Urutan merge

A1 (Lintar) sebelum A2 (Dipta). Kelima cabang implementasi (B1-B5) dibuat dari main yang memuat A1 dan A2. Urutan merge jendela B: B1, B2, B3, B4, B5.

## Catatan penutup

Sesi 3 selesai dengan 269 uji, 1017 asersi, 33 rute, Pint lulus, dan `lapak:periksa-buku` seimbang (6 mutasi).

**Titipan untuk Bela (Integrator Sesi 4):**
- AturanTransisi sudah teruji tetapi belum dipakai sampai tabel pesanan lahir.
- Pesanan milik orang lain dijawab 404.
- UPDATE bersyarat belum cukup untuk stok dan saldo.
- Persetujuan toko dan perubahan peran belum masuk log audit.

## Rekap kontribusi

```
== Commit per anggota (sesi-2..sesi-3, tanpa merge commit)
       7    Sekar
       6    Bambang
       5    Rani
       4    Bagas
       4    Dimas
```
