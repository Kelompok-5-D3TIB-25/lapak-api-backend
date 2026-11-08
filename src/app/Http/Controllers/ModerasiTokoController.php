<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DaftarTokoRequest;
use App\Http\Requests\TangguhkanTokoRequest;
use App\Http\Resources\TokoModerasiResource;
use App\Models\Toko;
use App\Services\ModerasiToko;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ModerasiTokoController extends Controller
{
    public function __construct(private readonly ModerasiToko $moderasi) {}

    public function index(DaftarTokoRequest $request): JsonResponse
    {
        $halaman = Toko::query()
            ->with('pemilik')
            ->withCount('produk')
            ->when(
                $request->validated('status'),
                fn (Builder $kueri, string $status) => $kueri->where('status', $status),
            )
            ->orderBy('id')
            ->paginate(config('lapak.katalog.per_halaman_bawaan'), ['*'], 'halaman');

        return $this->berhalaman($halaman, TokoModerasiResource::class);
    }

    public function setujui(Request $request, int $id): JsonResponse
    {
        $toko = $this->moderasi->setujui($this->toko($id), $request->user());

        return $this->sukses(TokoModerasiResource::make($toko->load('pemilik')));
    }

    public function tangguhkan(TangguhkanTokoRequest $request, int $id): JsonResponse
    {
        $toko = $this->moderasi->tangguhkan(
            $this->toko($id),
            $request->user(),
            $request->validated('alasan'),
        );

        return $this->sukses(TokoModerasiResource::make($toko->load('pemilik')));
    }

    private function toko(int $id): Toko
    {
        $toko = Toko::query()->findOrFail($id);

        Gate::authorize('moderasi', $toko);

        return $toko;
    }
}
