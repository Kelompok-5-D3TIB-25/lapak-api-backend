<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Uang;
use App\Http\Resources\MutasiResource;
use App\Services\BukuBesar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DompetController extends Controller
{
    public function __construct(private readonly BukuBesar $buku) {}

    /**
     * Saldo milik pengguna yang sedang masuk. Alamatnya tidak memuat pengenal dompet:
     * tidak ada angka yang dapat diganti klien untuk mengintip dompet orang lain.
     */
    public function show(Request $request): JsonResponse
    {
        $dompet = $this->buku->cariDompetPenjual($request->user());
        $saldo = Uang::dari($dompet?->saldo_rupiah ?? 0);

        return $this->sukses([
            'saldo' => ['rupiah' => $saldo, 'tampilan' => $saldo->format()],
            'jumlah_mutasi' => $dompet?->mutasi()->count() ?? 0,
        ]);
    }

    /** Rutenya dipasangi sesi.segar: rincian uang lebih peka daripada angka saldo (LP-15). */
    public function mutasi(Request $request): JsonResponse
    {
        $halaman = $this->buku->mutasiPenjual($request->user())
            ->paginate(config('lapak.katalog.per_halaman_bawaan'), ['*'], 'halaman');

        return $this->berhalaman($halaman, MutasiResource::class);
    }
}
