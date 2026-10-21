# Panduan setup

## Pertama Kali

*(iki neng terminal WSL/Linux)*

### 1. Clone Repository

```bash
git clone https://github.com/Rokkett2500/lapak-api.git
```

### 2. Masuk ke Folder Project

```bash
cd lapak-api
```

### 3. Ubah Permission Folder

```bash
sudo chmod -R 777 .
```

**titik (`.`) di belakang ini penting, jangan diabaikan**

### 4. Buat File `.env`

```bash
cp src/.env.example src/.env
```

jika pakai Windows CMD:

```cmd
copy src\.env.example src\.env
```

### 5. Jalankan Docker

```bash
docker compose up -d
```

terus liatin aplikasi Docker Desktop > Containers > klik 2x bagian `lapak-api`.

kalo dah keluar `nginx : ready to handle connections` atau semacamnya, berarti udah nyala

### 6. Buat Database SQLite

```bash
docker compose exec app touch database/database.sqlite
```

### 7. Generate Application Key

```bash
docker compose exec app php artisan key:generate
```

### 8. Jalankan Migrasi dan Seeder

```bash
docker compose exec app php artisan migrate --seed
```

### 8.1. Ubah Permission Folder Lagi

```bash
sudo chmod -R 777 .
```

(meneh, jogo2)

### 9. Buka Aplikasi

Buka [http://localhost:8000](http://localhost:8000).

---

## Setelah Setup Pertama Kali

Terus setelah 9, jalanin ini, dan ke depannya jalanin bawah ini aja

### 1. Masuk ke Folder Project

```bash
cd lapak-api
```

### 2. Fetch Update dari Remote

```bash
git fetch origin
```

### 3. Pindah ke Branch `main`

```bash
git checkout main
```

### 4. Pull Update Terbaru

```bash
git pull origin main
```

### 5. Buat Branch Fitur

```bash
git checkout -b fitur-nama
```

Contoh:

```bash
git checkout -b fitur-dipta
```

(Contone mau sek ws digarap dipta)

Kalau lanjut branch lama:

```bash
git checkout fitur-dipta
```

### 6. Jalankan Docker

```bash
docker compose up -d
```

### 7. Jalankan Migrasi Jika Ada yang Baru

Kalau ada migrasi baru dari teman:

```bash
docker compose exec app php artisan migrate
```

(jalanke ae nggo jogo2)

---

## Setelah Selesai Ngoding

### 1. Tambahkan Perubahan

```bash
git add -A
```

### 2. Commit Perubahan

```bash
git commit -m "jelaskan sik mbok ubah opo, singkat ae"
```

### 3. Push Branch ke GitHub

```bash
git push -u origin fitur-nama
```

Push berikutnya cukup:

```bash
git push
```

---

## Buat Pull Request

### 4. Buka Repository GitHub

Buka [https://github.com/Rokkett2500/lapak-api](https://github.com/Rokkett2500/lapak-api).

1. Klik **Compare & pull request**.
2. Pastikan:
   - **base:** `main`
   - **compare:** `fitur-nama` (branch punyamu tadi)
3. Klik **Create pull request**.
4. Tunggu biar yang lain merge.
