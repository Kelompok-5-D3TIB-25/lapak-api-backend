<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StatusPembayaran;
use App\Models\Checkout;
use App\Models\Pembayaran;
use Illuminate\Support\Str;

/**
 * Satu-satunya pintu perubahan tabel pembayaran.
 * buat() dan tandaiKedaluwarsa() dipanggil dari dalam transaksi pemanggil; terima() (jendela B,
 * PR B4) membuka GembokCheckout sendiri.
 */
final class LayananPembayaran
{
    /** LP-7: tagihan sebesar total checkout, dengan batas bayar menurut LP-10. */
    public function buat(Checkout $checkout): Pembayaran
    {
        $pembayaran = new Pembayaran;
        $pembayaran->forceFill([
            'checkout_id' => $checkout->id,
            'kode' => 'BYR-'.Str::ulid(),
            // Jumlahnya disalin dari checkout, tidak pernah dari klien (LP-6).
            'jumlah_rupiah' => $checkout->total_rupiah,
            'status' => StatusPembayaran::Menunggu,
            'batas_bayar_pada' => now()->addHours(config('lapak.batas_bayar_jam')),
        ])->save();

        return $pembayaran;
    }

    public function untuk(Checkout $checkout): Pembayaran
    {
        return Pembayaran::query()->where('checkout_id', $checkout->id)->firstOrFail();
    }

    /**
     * Dipanggil pembatalan otomatis, di dalam GembokCheckout.
     *
     * @return bool false bila tagihan sudah lunas, sudah kedaluwarsa, atau belum lewat batas
     */
    public function tandaiKedaluwarsa(Checkout $checkout): bool
    {
        // Dibaca ulang dengan kunci: di antara penjadwal memilih tagihan ini dan gembok didapat,
        // pembayarannya bisa saja sudah masuk.
        $pembayaran = Pembayaran::query()
            ->where('checkout_id', $checkout->id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($pembayaran->status !== StatusPembayaran::Menunggu
            || $pembayaran->batas_bayar_pada->isFuture()) {
            return false;
        }

        $pembayaran->forceFill(['status' => StatusPembayaran::Kedaluwarsa])->save();

        return true;
    }
}
