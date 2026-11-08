## Jenis unggahan
- [ ] Pembuka sesi
- [ ] A. Kontrak
- [ ] B. Implementasi
- [ ] Penutup sesi

## Ringkasan
Dua sampai empat kalimat: apa yang berubah dan mengapa.

## Aturan bisnis yang tersentuh
Contoh: LP-4, LP-13. Tulis "tidak ada" bila memang tidak ada.

## Berkas bersama yang disentuh
Sebutkan berkasnya dan alasan menyentuhnya. Tulis "tidak ada" bila tidak ada.

## Cara menguji
Perintah atau permintaan HTTP yang harus dijalankan peninjau, beserta hasil yang diharapkan.

## Catatan penggunaan AI
Bagian yang dibantu AI, alatnya, dan cara Anda memverifikasinya. Sudah dicatat di `docs/log-ai/<nama>.md`.

## Daftar periksa
- [ ] Cabang sudah di-rebase ke `origin/main` terbaru
- [ ] `./vendor/bin/pint --test` lolos
- [ ] `php artisan migrate:fresh --seed` lolos dari basis data kosong
- [ ] `./vendor/bin/pest` hijau
- [ ] Tidak ada rahasia, `.env`, atau berkas `vendor/` yang ikut ter-commit
- [ ] Saya dapat menjelaskan setiap baris pada PR ini tanpa bantuan
