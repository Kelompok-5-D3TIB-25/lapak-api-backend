<?php

/*
| Matriks otorisasi domain D2 Toko dan Katalog.
| Kunci: "METODE alamat". Nilai: daftar peran yang boleh, atau '*' untuk siapa pun yang sudah masuk.
*/
return [
    'GET api/v1/toko-saya' => '*',
    'POST api/v1/toko-saya' => ['pembeli'],
    'PATCH api/v1/toko-saya' => '*',
    'GET api/v1/toko-saya/produk' => ['penjual'],
    'POST api/v1/toko-saya/produk' => ['penjual'],
    'PATCH api/v1/toko-saya/produk/{id}' => ['penjual'],
    'DELETE api/v1/toko-saya/produk/{id}' => ['penjual'],
    'POST api/v1/toko-saya/produk/{id}/stok' => ['penjual'],

    'GET api/v1/admin/toko' => ['admin'],
    'POST api/v1/admin/toko/{id}/setujui' => ['admin'],
    'POST api/v1/admin/toko/{id}/tangguhkan' => ['admin'],
];
