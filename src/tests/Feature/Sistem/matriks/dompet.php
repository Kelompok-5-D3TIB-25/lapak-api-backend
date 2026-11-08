<?php

/*
| Matriks otorisasi domain D4 Pembayaran dan Saldo.
| Kunci: "METODE alamat". Nilai: daftar peran yang boleh, atau '*' untuk siapa pun yang sudah masuk.
*/
return [
    'GET api/v1/dompet' => ['penjual'],
    'GET api/v1/dompet/mutasi' => ['penjual'],

    'GET api/v1/buku-besar/dompet' => ['keuangan', 'auditor'],
    'GET api/v1/buku-besar/dompet/{id}/mutasi' => ['keuangan', 'auditor'],

    'GET api/v1/pembayaran/{kode}' => ['pembeli'],
];
