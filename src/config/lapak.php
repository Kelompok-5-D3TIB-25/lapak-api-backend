<?php

return [
    'versi' => '0.1.0',

    // LP-8: komisi marketplace, dipotong dari bagian penjual saat dana dilepas.
    'komisi_persen' => (int) env('LAPAK_KOMISI_PERSEN', 5),

    // LP-10: pesanan yang belum dibayar dibatalkan otomatis setelah sekian jam.
    'batas_bayar_jam' => (int) env('LAPAK_BATAS_BAYAR_JAM', 24),

    // LP-12: dana dilepas otomatis sekian hari setelah pesanan dikirim.
    'tahan_dana_hari' => (int) env('LAPAK_TAHAN_DANA_HARI', 3),

    // LP-16: batas bawah pencairan saldo penjual.
    'pencairan_minimum_rupiah' => (int) env('LAPAK_PENCAIRAN_MINIMUM', 50000),

    'katalog' => [
        'per_halaman_bawaan' => 12,
        'per_halaman_maksimum' => 60,
    ],

    'belanja' => [
        'butir_maksimum' => 20,
        'kuantitas_maksimum' => 99,
    ],

    // LP-14 dan LP-15: umur kunci sesi.
    'sesi' => [
        'masa_hidup_hari' => (int) env('LAPAK_SESI_MASA_HIDUP_HARI', 7),
        'menganggur_menit' => (int) env('LAPAK_SESI_MENGANGGUR_MENIT', 120),
        'segar_menit' => (int) env('LAPAK_SESI_SEGAR_MENIT', 10),
        // terakhir_dipakai_pada tidak ditulis ulang lebih sering dari ini.
        'jeda_sentuh_detik' => 60,
        // Sesi yang sudah mati disimpan sekian hari sebagai jejak, lalu dipangkas.
        'simpan_hari' => 30,
    ],

    'otorisasi' => [
        // Lama izin seorang pengguna diingat. Perubahan peran melupakannya saat itu juga;
        // angka ini hanya batas atas bila ada jalur yang lupa memanggil lupakan().
        'ingat_menit' => (int) env('LAPAK_IZIN_INGAT_MENIT', 5),
    ],

    'transaksi' => [
        // LP-15: checkout di atas nilai ini menuntut sesi segar.
        'batas_segar_rupiah' => (int) env('LAPAK_BATAS_SEGAR_RUPIAH', 2_000_000),
        // Permintaan per menit, per sesi, untuk pintu yang mengubah uang atau stok.
        'laju_per_menit' => 10,
        'alamat_maksimum' => 5,
    ],

    // Batas percobaan per menit untuk pintu yang menerima kata sandi.
    'laju' => [
        'masuk_per_akun' => 5,
        'masuk_per_ip' => 20,
        'konfirmasi' => 5,
    ],
];
