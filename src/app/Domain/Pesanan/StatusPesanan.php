<?php

declare(strict_types=1);

namespace App\Domain\Pesanan;

/** LP-10: daur hidup sebuah pesanan. */
enum StatusPesanan: string
{
    case MenungguPembayaran = 'menunggu_pembayaran';
    case Dibayar = 'dibayar';
    case Dikemas = 'dikemas';
    case Dikirim = 'dikirim';
    case Selesai = 'selesai';
    case Dibatalkan = 'dibatalkan';

    /** Status akhir tidak memiliki jalan keluar bagi siapa pun. */
    public function akhir(): bool
    {
        return $this === self::Selesai || $this === self::Dibatalkan;
    }
}
