<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\NamaPeran;
use App\Exceptions\PeranMengikutiToko;
use App\Http\Requests\DaftarPenggunaRequest;
use App\Http\Resources\PenggunaResource;
use App\Models\Pengguna;
use App\Services\PengelolaPeran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class PeranPenggunaController extends Controller
{
    public function __construct(private readonly PengelolaPeran $pengelola) {}

    /** GET /admin/pengguna: daftar akun beserta perannya, dapat disaring menurut peran. */
    public function index(DaftarPenggunaRequest $request): JsonResponse
    {
        $halaman = Pengguna::query()
            ->with('peran')
            ->when($request->validated('peran'), fn (Builder $kueri, string $peran) => $kueri->whereHas(
                'peran', fn (Builder $q) => $q->where('peran.nama', $peran),
            ))
            ->orderBy('id')
            ->paginate(config('lapak.katalog.per_halaman_bawaan'), ['*'], 'halaman');

        return $this->berhalaman($halaman, PenggunaResource::class);
    }

    /** PUT /admin/pengguna/{id}/peran/{peran}: idempoten, mengulanginya tidak mengubah apa pun. */
    public function beri(Request $request, int $id, NamaPeran $peran): JsonResponse
    {
        $sasaran = $this->sasaran($id, $peran);

        $this->pengelola->beri($sasaran, $peran, $request->user());

        return $this->sukses(PenggunaResource::make($sasaran->load('peran')));
    }

    /** DELETE /admin/pengguna/{id}/peran/{peran} */
    public function cabut(int $id, NamaPeran $peran): JsonResponse
    {
        $sasaran = $this->sasaran($id, $peran);

        $this->pengelola->cabut($sasaran, $peran);

        return $this->sukses(PenggunaResource::make($sasaran->load('peran')));
    }

    private function sasaran(int $id, NamaPeran $peran): Pengguna
    {
        $sasaran = Pengguna::query()->findOrFail($id);

        Gate::authorize('ubahPeran', $sasaran);

        if ($peran === NamaPeran::Penjual) {
            throw new PeranMengikutiToko;
        }

        return $sasaran;
    }
}
