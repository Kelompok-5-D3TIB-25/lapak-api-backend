<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Pengguna;
use App\Models\Toko;

final class TokoPolicy
{
    public function moderasi(Pengguna $pelaku, Toko $toko): bool
    {
        return $toko->pemilik_id !== $pelaku->id;
    }
}
