<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Balapan\PanggilHttp;
use App\Models\Pembayaran;
use App\Services\TandaTanganGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Gerbang pembayaran surrogate, untuk pengembangan dan uji manual.
 * Menyusun kabar pelunasan, menandatanganinya dengan rahasia dari .env, lalu mengirimkannya
 * ke pintu webhook — sama seperti gerbang sungguhan, minus jaringan sungguhan.
 *
 *   php artisan lapak:simulasi-bayar BYR-...                 # kabar sah
 *   php artisan lapak:simulasi-bayar BYR-... --jumlah=1      # kabar sah tapi jumlahnya salah
 *   php artisan lapak:simulasi-bayar BYR-... --ganda         # kabar yang sama, dua kali
 *   php artisan lapak:simulasi-bayar BYR-... --rahasia=tebakan  # pemalsuan tanda tangan
 */
final class SimulasiBayar extends Command
{
    use PanggilHttp;

    protected $signature = 'lapak:simulasi-bayar {kode_pembayaran}
                            {--jumlah= : Jumlah dalam rupiah, bawaan = jumlah tagihan}
                            {--ganda : Kirim kabar yang sama dua kali}
                            {--rahasia= : Rahasia penanda tangan (bawaan = rahasia dari .env)}';

    protected $description = 'Meniru gerbang pembayaran: menandatangani kabar pelunasan dan mengirimnya ke webhook';

    public function handle(TandaTanganGateway $tandaTangan): int
    {
        if (app()->isProduction()) {
            $this->error('Simulasi gerbang tidak boleh berjalan di produksi.');

            return self::FAILURE;
        }

        $kode = (string) $this->argument('kode_pembayaran');
        $tagihan = Pembayaran::query()->where('kode', $kode)->first();

        if ($tagihan === null) {
            $this->error("Tidak ada tagihan dengan kode {$kode}.");

            return self::FAILURE;
        }

        $jumlah = (int) ($this->option('jumlah') ?? $tagihan->jumlah_rupiah);

        $badan = json_encode([
            'id_peristiwa' => 'evt_'.Str::lower(Str::random(16)),
            'kode_pembayaran' => $kode,
            'jumlah_rupiah' => $jumlah,
        ], JSON_THROW_ON_ERROR);

        $rahasia = $this->option('rahasia') ?? (string) config('lapak.gateway.rahasia');
        $tanda = $tandaTangan->buat($badan, rahasia: $rahasia);

        $kiriman = 1;
        do {
            [$status, $json] = $this->panggil('POST', '/api/v1/webhook/pembayaran',
                json_decode($badan, true),
                [TandaTanganGateway::HEADER => $tanda],
            );

            $this->line("Kiriman ke-{$kiriman}: HTTP {$status}");
            $this->line(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $kiriman++;
        } while ($this->option('ganda') && $kiriman <= 2);

        return self::SUCCESS;
    }
}
