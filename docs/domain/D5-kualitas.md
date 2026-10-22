# D5 — Otorisasi, Audit, dan Kualitas

- Pemilik: @Rokkett2500
- Tabel: `peran`, `izin`, `log_audit` (Sesi 3)
- Aturan: LP-2, LP-17, dan bentuk galat seluruh domain

## Pertanyaan yang dijawab

Siapa boleh melakukan apa, siapa yang sudah melakukan apa, dan apakah `main` layak dipercaya?

## Yang dibutuhkan dari domain lain

Tidak ada. D5 menyediakan kontrak yang dipakai semua domain.

## Yang disediakan untuk domain lain

- `App\Http\Concerns\ResponsApi` — amplop `sukses`, `data`, `meta`.
- `App\Exceptions\GalatDomain` — induk galat aturan bisnis.
- `App\Exceptions\PenanganGalatApi` — satu pintu galat API.
- `App\Http\Middleware\PaksaJson` — paksa `Accept: application/json`.
- `App\Http\Middleware\IdPermintaan` — `X-Request-Id` di respons dan log.
- `GET /api/v1/health`.
- `.github/workflows/ci.yml` — gerbang `kualitas`.

## Kontrak galat

Semua galat API memakai amplop:

```json
{
  "sukses": false,
  "galat": {
    "kode": "LP-13",
    "pesan": "Saldo tidak mencukupi untuk pemindahan ini.",
    "rincian": { "dompet": "penjual:1", "saldo": 0, "diminta": 5000 }
  }
}
```

Kode HTTP: 401 `BELUM_MASUK`, 403 `DITOLAK`, 404 `TIDAK_DITEMUKAN`, 405 `METODE_TIDAK_DIIZINKAN`, 422 `VALIDASI`, 429 `TERLALU_SERING`, 500 `GALAT_SERVER`.

## Aturan

- Controller tidak merakit galat sendiri.
- Galat tak terduga tidak membocorkan pesan asli saat `APP_DEBUG=false`.
- Setiap respons API membawa `X-Request-Id` buatan server.
- CI berjalan pada setiap PR ke `main`: Pint, migrasi + seeder, `lapak:periksa-buku`, Pest.
- Perubahan `.github/`, `config/lapak.php`, dan `composer.json` wajib ditinjau pemilik D5.

## Batas domain

D5 tidak memiliki data bisnis. Tabel peran, izin, dan log audit baru dibuat pada Sesi 3.
