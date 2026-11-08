<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\SesiTidakSegar;
use App\Models\Sesi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * LP-15: memegang kunci yang sah belum cukup untuk tindakan sensitif.
 * Pemegangnya harus membuktikan kata sandi pada sesi ini dalam beberapa menit terakhir.
 * Pasang SETELAH auth:sesi, dengan alias sesi.segar.
 */
final class SesiSegar
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Sesi::dariPermintaan($request)->segar()) {
            throw new SesiTidakSegar;
        }

        return $next($request);
    }
}
