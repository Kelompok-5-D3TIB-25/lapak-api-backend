<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\NamaIzin;
use App\Services\PemeriksaIzin;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class PeriksaIzin
{
    public function __construct(private readonly PemeriksaIzin $pemeriksa) {}

    public function handle(Request $request, Closure $next, string $izin): Response
    {
        if (! $this->pemeriksa->punya($request->user(), NamaIzin::from($izin))) {
            throw new AuthorizationException;
        }

        return $next($request);
    }
}
