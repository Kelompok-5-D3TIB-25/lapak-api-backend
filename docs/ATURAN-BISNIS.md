# Aturan Bisnis Lapak

Kolom "Ditegakkan di" hanya diisi integrator pada penutup sesi.

| Kode | Aturan | Ditegakkan di |
|---|---|---|
| LP-1 | Seluruh nilai uang adalah bilangan bulat rupiah. Tipe pecahan dilarang di kolom, kode, dan JSON. | App\Domain\Uang, kolom berakhiran _rupiah, uji arsitektur |
| LP-2 | Kewenangan ditentukan izin yang melekat pada peran, bukan nama peran. Satu akun boleh memegang lebih dari satu peran. | |
| LP-3 | Satu pengguna memiliki paling banyak satu toko. Toko baru berstatus menunggu sampai disetujui admin. | Sebagian: unique pada toko.pemilik_id |
| LP-4 | Produk hanya tampil dan dapat dibeli bila berstatus aktif, tokonya aktif, dan stoknya mencukupi. Stok tidak pernah negatif. | Sebagian: Produk::scopeTampil, PenyusunBelanja |
| LP-5 | Penjual tidak dapat membeli produk dari tokonya sendiri. | |
| LP-6 | Harga, ongkir, dan total dihitung server dari basis data. Angka kiriman klien diabaikan. Harga dibekukan pada butir pesanan saat checkout. | Sebagian: SimulasiBelanjaRequest, PenyusunBelanja |
| LP-7 | Satu checkout menghasilkan satu pesanan per toko dan dibayar dengan satu pembayaran. | Sebagian: PenghitungBelanja |
| LP-8 | Komisi marketplace 5% dari subtotal produk, dibulatkan setengah ke atas, dipotong dari bagian penjual saat dana dilepas. Ongkir sepenuhnya milik penjual. | Sebagian: PenghitungBelanja, RincianToko::bagianPenjual |
| LP-9 | Nomor pesanan berformat LPK-YYYYMMDD-NNNNNN dan unik seumur hidup sistem. | |
| LP-10 | Status pesanan bergerak menunggu pembayaran, dibayar, dikemas, dikirim, selesai. Pembatalan hanya sebelum dikirim. | |
| LP-11 | Setiap permintaan yang mengubah uang atau stok wajib membawa `Idempotency-Key`. | |
| LP-12 | Dana pembeli ditahan di escrow dan dilepas setelah pembeli mengonfirmasi terima, atau otomatis 3 hari setelah dikirim. | |
| LP-13 | Saldo hanya berubah lewat pemindahan antardompet yang tercatat sebagai sepasang mutasi berjumlah nol. Mutasi tidak diubah dan tidak dihapus. Hanya dompet eksternal yang boleh negatif. | BukuBesar, MutasiSaldo::booted, lapak:periksa-buku |
| LP-14 | Kunci sesi ditampilkan satu kali dan disimpan sebagai hash. Sesi berlaku paling lama 7 hari. | |
| LP-15 | Tindakan sensitif menuntut sesi segar: kata sandi dikonfirmasi ulang dalam 10 menit terakhir. | |
| LP-16 | Pencairan saldo minimal Rp 50.000, disetujui peran keuangan, dan penyetuju tidak boleh orang yang sama dengan pengaju. | |
| LP-17 | Setiap tindakan sensitif tercatat pada log audit yang hanya dapat ditambah dan berantai hash. | |
