# Peta Skema

## Penomoran migration

Pola: `2026_10_SS_DNNNNN_nama.php`

- `SS` = nomor sesi (01)
- `D` = nomor domain (1–5)
- `NNNNN` = nomor urut

Contoh: `2026_10_01_200003_buat_tabel_produk.php` adalah migration ketiga D2 pada Sesi 1.

Dengan pola ini urutan migrasi mengikuti urutan domain, bukan siapa yang lebih dulu mengetik `make:migration`.

## Kepemilikan tabel

| Domain | Tabel | Migration |
|---|---|---|
| D1 | `pengguna` | `2026_10_01_100001_buat_tabel_pengguna.php` |
| D1 | `alamat` | Sesi 4 |
| D2 | `toko` | `2026_10_01_200001_buat_tabel_toko.php` |
| D2 | `kategori` | `2026_10_01_200002_buat_tabel_kategori.php` |
| D2 | `produk` | `2026_10_01_200003_buat_tabel_produk.php` |
| D3 | `keranjang` | `2026_10_01_300001_buat_tabel_keranjang.php` |
| D3 | `butir_keranjang` | (satu migration dengan `keranjang`) |
| D3 | `checkout`, `pesanan`, `butir_pesanan` | Sesi 4 |
| D4 | `dompet` | `2026_10_01_400001_buat_tabel_dompet.php` |
| D4 | `mutasi_saldo` | `2026_10_01_400002_buat_tabel_mutasi_saldo.php` |
| D4 | `pembayaran` | Sesi 4 |
| D5 | `peran`, `izin`, `log_audit` | Sesi 3 |
| D5 | `kunci_idempotensi` | Sesi 4 |

## Arah foreign key

Foreign key hanya boleh menunjuk domain bernomor sama atau lebih kecil.

```text
pengguna (D1) <-- toko (D2) <-- produk (D2) <-- butir_keranjang (D3)
pengguna (D1) <-- keranjang (D3)
pengguna (D1) <-- dompet (D4) <-- mutasi_saldo (D4)
pengguna (D1) <-- alamat (D1)
pengguna (D1) <-- checkout (D3) <-- pesanan (D3) <-- butir_pesanan (D3)
toko (D2) <-- pesanan (D3)          produk (D2) <-- butir_pesanan (D3)
checkout (D3) <-- pembayaran (D4)
pengguna (D1) <-- kunci_idempotensi (D5)
```

`pesanan` tidak menunjuk ke `pembayaran`, walau itu yang pertama terpikir: D3 tidak boleh menunjuk ke D4. Keduanya menunjuk ke `checkout`, induk bersama milik D3 (LP-7). `pesanan` juga tidak menunjuk ke `alamat`: alamat disalin ke kolom `alamat_kirim` pada saat checkout.

Kontrak tanpa tabel: `ResponsApi` dan `GalatDomain` (D5) dipakai semua domain; `Uang` (D3) dipakai D2, D3, dan D4.

## Rincian kolom

### `pengguna` (D1)

| Kolom | Tipe | Catatan |
|---|---|---|
| `id` | bigint PK | |
| `nama` | string(100) | |
| `surel` | string(120) | unik, dinormalkan ke huruf kecil |
| `telepon` | string(20) | nullable |
| `kata_sandi` | string(255) | di-hash |
| `status` | string(20) | `aktif`, `ditangguhkan` |
| timestamps | created/updated | |

### `toko` (D2)

| Kolom | Tipe | Catatan |
|---|---|---|
| `id` | bigint PK | |
| `pemilik_id` | FK `pengguna.id` | unik (LP-3) |
| `nama` | string(80) | |
| `slug` | string(100) | unik |
| `kota` | string(60) | |
| `deskripsi` | text | nullable |
| `ongkir_rupiah` | unsignedBigInteger | default 0 |
| `status` | string(20) | `menunggu`, `aktif`, `ditangguhkan`; index |

### `kategori` (D2)

| Kolom | Tipe | Catatan |
|---|---|---|
| `id` | bigint PK | |
| `nama` | string(60) | |
| `slug` | string(80) | unik |

### `produk` (D2)

| Kolom | Tipe | Catatan |
|---|---|---|
| `id` | bigint PK | |
| `toko_id` | FK `toko.id` | restrict on delete |
| `kategori_id` | FK `kategori.id` | restrict on delete |
| `nama` | string(120) | |
| `slug` | string(140) | unik |
| `deskripsi` | text | nullable |
| `harga_rupiah` | unsignedBigInteger | index |
| `stok` | unsignedInteger | default 0 |
| `berat_gram` | unsignedInteger | default 0 |
| `status` | string(20) | `draf`, `aktif`, `arsip` |

Index tambahan: `['status', 'kategori_id']`, `harga_rupiah`.

### `keranjang` (D3)

| Kolom | Tipe | Catatan |
|---|---|---|
| `id` | bigint PK | |
| `pengguna_id` | FK `pengguna.id` | unik; cascade delete |

### `butir_keranjang` (D3)

| Kolom | Tipe | Catatan |
|---|---|---|
| `id` | bigint PK | |
| `keranjang_id` | FK `keranjang.id` | cascade delete |
| `produk_id` | FK `produk.id` | restrict delete |
| `kuantitas` | unsignedInteger | |
| unik | `['keranjang_id', 'produk_id']` | satu baris per produk |

### `dompet` (D4)

| Kolom | Tipe | Catatan |
|---|---|---|
| `id` | bigint PK | |
| `kode` | string(40) | unik |
| `jenis` | string(20) | `eksternal`, `escrow`, `pendapatan`, `penjual` |
| `pengguna_id` | FK `pengguna.id` | nullable untuk dompet sistem |
| `saldo_rupiah` | bigInteger | hanya berubah lewat `BukuBesar` |

### `mutasi_saldo` (D4)

| Kolom | Tipe | Catatan |
|---|---|---|
| `id` | bigint PK | |
| `dompet_id` | FK `dompet.id` | |
| `kelompok` | uuid | satu pemindahan = dua baris |
| `jenis` | string(20) | `dana_masuk`, `lepas_dana`, `komisi`, `pengembalian`, `pencairan` |
| `jumlah_rupiah` | bigInteger | bertanda |
| `saldo_setelah` | bigInteger | |
| `rujukan` | string(60) | |
| `keterangan` | string(160) | nullable |
| `created_at` | timestamp | tanpa `updated_at` (append-only) |

## Aturan skema

- Semua nilai uang adalah bilangan bulat: `bigInteger`/`unsignedBigInteger` dengan akhiran `_rupiah`. Tidak ada `decimal`, `float`, atau `double` (LP-1).
- Status disimpan sebagai string(20) dan dipetakan ke enum PHP.
- `mutasi_saldo` tidak boleh diubah atau dihapus (LP-13).

## Urutan kunci baris

Berlaku sejak Sesi 4. Transaksi yang mengunci lebih dari satu baris mengambil kuncinya menurut urutan di bawah ini. Sebuah transaksi boleh melompati anak tangga, tetapi tidak pernah naik kembali.

| Urut | Baris | Dikunci oleh |
| --- | --- | --- |
| 1 | `pengguna`, lalu `alamat` miliknya | `BukuAlamat` |
| 2 | `keranjang`, lalu `butir_keranjang` | `LayananCheckout` |
| 3 | `checkout` | `GembokCheckout` |
| 4 | `pembayaran` | `LayananPembayaran` |
| 5 | `pesanan` | `MesinStatusPesanan` |
| 6 | `produk`, urut nomor menaik | `PenjagaStok` |
| 7 | `dompet`, urut nomor menaik | `BukuBesar` |

Middleware `idempoten` mencatat kuncinya sebelum anak tangga pertama. Catatan itu baris baru, dan tidak ada transaksi yang memintanya setelah memegang kunci dari tabel di atas.

Tiga aturan menyertainya:

1. Kunci hanya diambil di dalam `DB::transaction()`. Di luar transaksi, kunci lepas begitu pernyataannya selesai.
2. Kunci dulu, baca kemudian. Data yang menjadi dasar keputusan dibaca dengan `lockForUpdate()` setelah kunci induknya didapat. Pada MySQL, `SELECT` biasa di dalam transaksi melihat potret lama, bukan keadaan terkini.
3. Tidak ada panggilan ke layanan luar dan tidak ada pekerjaan lama di dalam transaksi. Selama transaksi terbuka, pembeli lain menunggu di depan baris yang dikuncinya.
