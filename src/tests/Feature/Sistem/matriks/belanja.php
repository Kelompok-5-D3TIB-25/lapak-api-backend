<?php

/*
| Matriks otorisasi domain D3 Keranjang dan Pesanan.
| Kunci: "METODE alamat". Nilai: daftar peran yang boleh, atau '*' untuk siapa pun yang sudah masuk.
*/
return [
    'GET api/v1/keranjang' => ['pembeli'],
    'DELETE api/v1/keranjang' => ['pembeli'],
    'PUT api/v1/keranjang/butir/{produkId}' => ['pembeli'],
    'DELETE api/v1/keranjang/butir/{produkId}' => ['pembeli'],

    'POST api/v1/checkout' => ['pembeli'],
    'GET api/v1/pesanan' => ['pembeli'],
    'GET api/v1/pesanan/{nomor}' => ['pembeli'],
];
