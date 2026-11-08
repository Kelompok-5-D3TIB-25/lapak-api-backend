# Aturan Bisnis Lapak

Kolom "Ditegakkan di" hanya diisi integrator pada penutup sesi.

| Kode | Aturan | Ditegakkan di |
|---|---|---|
| LP-1 | Seluruh nilai uang adalah bilangan bulat rupiah. Tipe pecahan dilarang di kolom, kode, dan JSON. | App\Domain\Uang, kolom berakhiran _rupiah, uji arsitektur |
| LP-2 | Kewenangan ditentukan izin yang melekat pada peran, bukan nama peran. Satu akun boleh memegang lebih dari satu peran. | NamaPeran::izinBawaan, PemeriksaIzin, middleware izin, PengelolaPeran, MatriksOtorisasiTest |
| LP-3 | Satu pengguna memiliki paling banyak satu toko. Toko baru berstatus menunggu sampai disetujui admin. | Sebagian penuh: unique pada toko.pemilik_id, izin toko.buka, ModerasiToko, TokoPolicy |
| LP-4 | Produk hanya tampil dan dapat dibeli bila berstatus aktif, tokonya aktif, dan stoknya mencukupi. Stok tidak pernah negatif. | Produk::scopeTampil, PenyusunBelanja, PenjagaStok; skenario rebutan-stok, rebutan-checkout, dan penyesuaian-stok |
| LP-5 | Penjual tidak dapat membeli produk dari tokonya sendiri. | PenyusunBelanja::pilah, LayananKeranjang::aturButir, LayananCheckout |
| LP-6 | Harga, ongkir, dan total dihitung server dari basis data. Angka kiriman klien diabaikan. Harga dibekukan pada butir pesanan saat checkout. | SimulasiBelanjaRequest, PenyusunBelanja, CheckoutRequest, kolom beku pada butir_pesanan, LayananPembayaran::terima |
| LP-7 | Satu checkout menghasilkan satu pesanan per toko dan dibayar dengan satu pembayaran. | PenghitungBelanja, LayananCheckout, unique pada pembayaran.checkout_id |
| LP-8 | Komisi marketplace 5% dari subtotal produk, dibulatkan setengah ke atas, dipotong dari bagian penjual saat dana dilepas. Ongkir sepenuhnya milik penjual. | Sebagian: PenghitungBelanja, RincianToko::bagianPenjual |
| LP-9 | Nomor pesanan berformat LPK-YYYYMMDD-NNNNNN dan unik seumur hidup sistem. | PenomorPesanan, Pesanan::booted, unique pada pesanan.nomor |
| LP-10 | Status pesanan bergerak menunggu pembayaran, dibayar, dikemas, dikirim, selesai. Pembatalan hanya sebelum dikirim. | Sebagian: AturanTransisi, MesinStatusPesanan, PembatalanOtomatis; pintu penjual dan pembatalan oleh pembeli menyusul |
| LP-11 | Setiap permintaan yang mengubah uang atau stok wajib membawa `Idempotency-Key`. | Middleware idempoten, RuteIdempotenTest; skenario kunci-ganda |
| LP-12 | Dana pembeli ditahan di escrow dan dilepas setelah pembeli mengonfirmasi terima, atau otomatis 3 hari setelah dikirim. | Sebagian: dana masuk escrow pada LayananPembayaran::terima; pelepasannya menyusul |
| LP-13 | Saldo hanya berubah lewat pemindahan antardompet yang tercatat sebagai sepasang mutasi berjumlah nol. Mutasi tidak diubah dan tidak dihapus. Hanya dompet eksternal yang boleh negatif. | BukuBesar, MutasiSaldo::booted, lapak:periksa-buku dengan lima invarian |
| LP-14 | Kunci sesi ditampilkan satu kali dan disimpan sebagai hash. Sesi berlaku paling lama 7 hari. | KunciSesi, PenerbitSesi, Sesi::scopeBerlaku, lapak:pangkas-sesi |
| LP-15 | Tindakan sensitif menuntut sesi segar: kata sandi dikonfirmasi ulang dalam 10 menit terakhir. | Sebagian: SesiSegar, SesiController::konfirmasi, PenjagaTransaksi; dipakai pada cabut sesi, riwayat mutasi, perubahan peran, keputusan moderasi, buku besar, dan checkout bernilai besar |
| LP-16 | Pencairan saldo minimal Rp 50.000, disetujui peran keuangan, dan penyetuju tidak boleh orang yang sama dengan pengaju. | |
| LP-17 | Setiap tindakan sensitif tercatat pada log audit yang hanya dapat ditambah dan berantai hash. | |
