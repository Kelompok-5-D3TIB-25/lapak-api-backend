<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API ini hanya berbicara JSON. Tanpa middleware ini, klien yang lupa mengirim
 * header Accept akan menerima halaman HTML atau pengalihan saat terjadi galat.
 */
final class PaksaJson
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
