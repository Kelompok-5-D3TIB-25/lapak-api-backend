<?php

declare(strict_types=1);

namespace App\Domain\Pesanan;

enum PelakuPesanan: string
{
    case Pembeli = 'pembeli';
    case Penjual = 'penjual';

    /** Konfirmasi pembayaran dan penjadwal. Tidak pernah berasal dari permintaan pengguna. */
    case Sistem = 'sistem';
}
