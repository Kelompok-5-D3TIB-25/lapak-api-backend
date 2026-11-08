<?php

declare(strict_types=1);

namespace App\Domain\Belanja;

enum AlasanBermasalah: string
{
    /** Produk tidak ada, draf, arsip, atau tokonya tidak aktif. Klien tidak diberi tahu yang mana. */
    case TidakTersedia = 'tidak_tersedia';

    case StokKurang = 'stok_kurang';

    /** LP-5: penjual tidak membeli dari tokonya sendiri. */
    case ProdukSendiri = 'produk_sendiri';
}
