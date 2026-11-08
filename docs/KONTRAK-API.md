# Kontrak API

Base path: `/api/v1`

API hanya berbicara JSON. Middleware `PaksaJson` menetapkan `Accept: application/json` agar galat tidak keluar sebagai HTML.

## Amplop sukses

```json
{
  "sukses": true,
  "data": {},
  "meta": null
}
```

### Meta halaman

```json
{
  "halaman": 1,
  "per_halaman": 12,
  "total": 10,
  "halaman_terakhir": 1
}
```

## Amplop galat

```json
{
  "sukses": false,
  "galat": {
    "kode": "LP-4",
    "pesan": "Stok Selendang Batik Sutra tidak mencukupi.",
    "rincian": {
      "produk_id": 3,
      "diminta": 1,
      "tersedia": 0
    }
  }
}
```

## Kode galat

| HTTP | Kode | Kapan |
|---|---|---|
| 401 | `BELUM_MASUK` | Belum masuk atau sesi tidak berlaku |
| 401 | `KREDENSIAL_SALAH` | Surel atau kata sandi salah saat masuk. Pesan sama untuk surel tak terdaftar dan kata sandi salah. |
| 403 | `DITOLAK` | Tidak berwenang |
| 403 | `LP-15` | Tindakan sensitif menuntut konfirmasi ulang kata sandi via POST /api/v1/sesi/konfirmasi. |
| 404 | `TIDAK_DITEMUKAN` | Sumber daya tidak ada |
| 405 | `METODE_TIDAK_DIIZINKAN` | Metode HTTP salah |
| 422 | `VALIDASI` | Validasi masukan gagal |
| 422/409 | `LP-*` | Pelanggaran aturan bisnis (`GalatDomain`) |
| 429 | `TERLALU_SERING` | Pembatasan laju |
| 500 | `GALAT_SERVER` | Galat tak terduga; rincian tidak dikirim ke klien |

Pelanggaran aturan bisnis membawa kode `LP-1` sampai `LP-17` pada `galat.kode`. Status HTTP ditentukan pemilik aturan (umumnya 422, stok 409).

## Header

| Header | Arah | Catatan |
|---|---|---|
| `Accept: application/json` | permintaan | Disarankan; dipaksa middleware |
| `X-Request-Id` | respons | UUID buatan server, juga muncul di log |

## Autentikasi

Setiap permintaan yang bukan publik harus menyertakan header:
```
Authorization: Bearer <kunci>
```
Kunci diperoleh dari `POST /api/v1/masuk`. Simpan kunci di tempat yang aman; server tidak dapat mengembalikannya setelah respons pertama dikirim.

## Otorisasi

- Kewenangan ditentukan izin yang melekat pada peran, bukan nama peran (LP-2). Satu akun boleh memegang beberapa peran; izinnya adalah gabungan izin seluruh perannya.
- Rute terlindung menyebut paling banyak satu izin: `->middleware('izin:<nama>')`. Bila dua peran membutuhkan pintu yang sama, keduanya diberi izin yang sama. Rute tidak pernah menyebut nama peran.
- Setiap rute terlindung memiliki tepat satu baris pada `tests/Feature/Sistem/matriks/<domain>.php`. Rute yang terbuka bagi siapa pun yang sudah masuk ditulis `'*'`, tidak dibiarkan tanpa baris.
- Urutan pemeriksaan selalu sama: kunci sah (401), memegang izin (403 `DITOLAK`), sesi segar bila rutenya menuntut (403 `LP-15`). Baru setelah ketiganya lolos, masukan divalidasi dan objek dicari.
- Pertanyaan "objek yang ini boleh?" dijawab di dalam controller: kueri berlingkup (objek milik orang lain dijawab 404) atau Policy (403 `DITOLAK`).
- Badan 403 `DITOLAK` selalu sama dan tidak menyebut izin apa yang kurang.
- `GET /saya` menyertakan `pengguna.peran` dan `izin`.
- Peran hanya berubah lewat `App\Services\PengelolaPeran`. Setiap perubahan mencabut seluruh sesi akun itu.

| Peran | Izin |
| --- | --- |
| `pembeli` | `keranjang.kelola`, `toko.buka`, `pesanan.buat` |
| `penjual` | `produk.kelola`, `dompet.lihat` |
| `admin` | `toko.moderasi`, `peran.kelola` |
| `keuangan` | `buku-besar.lihat` |
| `auditor` | `buku-besar.lihat` |

## Uang dan waktu

- Semua nilai uang adalah bilangan bulat rupiah. Field berakhiran `_rupiah`.
- JSON uang berupa angka bulat, misalnya `185000`, bukan `"185000"` dan bukan `185000.0`.
- Untuk tampilan, `Uang::format()` menghasilkan `Rp 1.250.000`.
- Waktu memakai ISO 8601, misalnya `2026-10-06T04:25:36+00:00`.

## Endpoint Sesi 1

| Metode | Path | Nama rute | Keterangan |
|---|---|---|---|
| GET | `/api/v1/health` | `v1.health` | Status layanan, versi, waktu |
| GET | `/api/v1/produk` | `v1.produk.index` | Katalog berhalaman |
| GET | `/api/v1/produk/{slug}` | `v1.produk.show` | Detail produk tampil |
| GET | `/api/v1/toko/{slug}` | `v1.toko.show` | Profil toko aktif |
| POST | `/api/v1/registrasi` | `v1.registrasi` | Daftar pengguna |
| POST | `/api/v1/simulasi-belanja` | `v1.belanja.simulasi` | Simulasi belanja lintas toko |

## Aturan per endpoint

### `GET /produk`

Parameter: `kategori`, `toko`, `cari`, `harga_min`, `harga_maks`, `tersedia`, `urut` (`terbaru`, `harga_naik`, `harga_turun`), `per_halaman` (1–60), `halaman`.

Hanya produk berstatus `aktif`, dari toko `aktif`, dengan `stok > 0` (LP-4).

### `POST /registrasi`

```json
{
  "nama": "Rina Kartika",
  "surel": "Rina@Contoh.test",
  "telepon": "081234567890",
  "kata_sandi": "BatikSolo2026",
  "konfirmasi_kata_sandi": "BatikSolo2026"
}
```

Respons `201`: `id`, `nama`, `surel` (huruf kecil), `telepon`, `status`, `terdaftar_pada`. Tidak pernah memuat `kata_sandi`.

### `POST /simulasi-belanja`

```json
{
  "butir": [
    { "produk_id": 2, "kuantitas": 2 },
    { "produk_id": 5, "kuantitas": 3 }
  ]
}
```

Hanya `produk_id` dan `kuantitas` yang divalidasi. `harga_satuan`, `total_bayar`, dan kolom lain dari klien diabaikan (LP-6). Satu toko menghasilkan satu rincian (LP-7). Komisi dihitung dari subtotal, ongkir utuh untuk penjual (LP-8).

## Idempotensi

- Setiap permintaan yang mengubah uang atau stok wajib membawa header `Idempotency-Key` (LP-11):
  16 sampai 64 huruf, angka, garis bawah, atau tanda hubung. UUID memenuhi syarat ini.
- Klien membuat SATU kunci untuk satu niat, misalnya satu kali menekan tombol bayar, dan memakai
  kunci yang sama setiap kali mengulang permintaan itu karena jaringan putus atau batas waktu habis.
- Permintaan pertama yang berhasil (2xx) dicatat bersama responsnya. Pengulangan dengan kunci dan
  badan yang sama menerima respons yang sama, ditandai header `Idempotency-Replayed: true`, dan
  tidak berefek lagi.
- Permintaan yang gagal tidak meninggalkan catatan. Setelah sebabnya dibetulkan, kunci yang sama
  boleh dipakai lagi.
- Kunci yang sama dengan badan yang berbeda dijawab 422 `LP-11`. Kunci berlaku per pengguna.
- Catatan kunci dan seluruh perubahan data berada dalam satu transaksi basis data. Rute yang
  memasang `idempoten` karenanya tidak boleh memanggil layanan luar di dalam controller-nya.

## Webhook pembayaran

- `POST /webhook/pembayaran` adalah satu-satunya rute tulis tanpa kunci sesi. Pemanggilnya
  gerbang pembayaran. Pembeli tidak memiliki pintu apa pun untuk menandai tagihannya lunas.
- Gerbang mengirim header `X-Gateway-Signature: t=<detik unix>,v1=<tanda>`, dengan `<tanda>`
  berupa HMAC-SHA256 atas teks `<t>.<badan mentah>` memakai rahasia bersama `LAPAK_GATEWAY_RAHASIA`.
- Tanpa header, rahasia salah, badan diubah, atau selisih waktu lebih dari 5 menit: jawabannya
  selalu 401 `TANDA_TANGAN_SALAH` yang sama. Rahasia yang kosong menolak seluruh webhook.
- Badan: `id_peristiwa`, `kode_pembayaran`, `jumlah_rupiah`. Jumlah dicocokkan dengan tagihan di
  basis data; selisih satu rupiah pun dijawab 422 `JUMLAH_TIDAK_COCOK` dan tidak melunasi apa pun.
- Kabar yang sama boleh tiba berkali-kali. Yang pertama dijawab `"hasil": "dilunasi"`, sisanya
  `"hasil": "sudah_diproses"`, dan uangnya dibukukan satu kali.
