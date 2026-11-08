<?php

/*
| Matriks otorisasi domain D1 Identitas dan Sesi.
| Kunci: "METODE alamat". Nilai: daftar peran yang boleh, atau '*' untuk siapa pun yang sudah masuk.
*/
return [
    'GET api/v1/saya' => '*',
    'POST api/v1/keluar' => '*',
    'GET api/v1/sesi' => '*',
    'POST api/v1/sesi/konfirmasi' => '*',
    'DELETE api/v1/sesi/{pengenal}' => '*',

    'GET api/v1/admin/pengguna' => ['admin'],
    'PUT api/v1/admin/pengguna/{id}/peran/{peran}' => ['admin'],
    'DELETE api/v1/admin/pengguna/{id}/peran/{peran}' => ['admin'],

    'GET api/v1/alamat' => ['pembeli'],
];
