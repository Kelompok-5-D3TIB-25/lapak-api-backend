<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Balapan\SkenarioBalapan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use ReflectionClass;

/**
 * Melepas beberapa proses PHP sungguhan yang melakukan hal yang sama pada saat yang sama,
 * lalu memeriksa bahwa aturan yang tidak boleh dilanggar memang tidak dilanggar.
 *
 * Contoh: php artisan lapak:balapan rebutan-stok
 *         php artisan lapak:balapan --semua
 */
final class Balapan extends Command
{
    protected $signature = 'lapak:balapan {skenario? : Nama skenario, misalnya rebutan-stok}
                            {--semua : Jalankan seluruh skenario yang terdaftar}
                            {--proses= : Jumlah proses, menimpa bawaan skenario}';

    protected $description = 'Uji balapan serentak pada MySQL: beberapa proses, satu aturan';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('lapak:balapan membangkitkan proses serentak sungguhan; tidak untuk lingkungan produksi.');

            return self::FAILURE;
        }

        if (config('database.default') === 'sqlite' || DB::connection()->getDriverName() === 'sqlite') {
            $this->error('Basis data saat ini SQLite, yang hanya mengizinkan satu penulis dan');
            $this->error('mengabaikan lockForUpdate(). Hasilnya tidak berarti. Pakai MySQL.');

            return self::FAILURE;
        }

        if ($this->option('semua')) {
            $gagal = 0;
            foreach (array_keys(self::daftar()) as $nama) {
                $gagal += $this->jalankan($nama) ? 0 : 1;
            }

            if ($gagal > 0) {
                $this->error("{$gagal} skenario melanggar aturannya.");

                return self::FAILURE;
            }

            return self::SUCCESS;
        }

        $nama = (string) $this->argument('skenario');
        if ($nama === '') {
            $this->error('Sebutkan nama skenario, atau pakai --semua.');
            $this->line('Tersedia: '.implode(', ', array_keys(self::daftar())));

            return self::FAILURE;
        }

        return $this->jalankan($nama) ? self::SUCCESS : self::FAILURE;
    }

    /** @return array<string, class-string<SkenarioBalapan>> nama skenario -> kelasnya */
    public static function daftar(): array
    {
        $daftar = [];

        foreach (glob(app_path('Balapan/*.php')) ?: [] as $berkas) {
            $kelas = 'App\\Balapan\\'.basename($berkas, '.php');
            if (! class_exists($kelas)) {
                continue;
            }

            $refleksi = new ReflectionClass($kelas);
            if ($refleksi->isInstantiable() && $refleksi->implementsInterface(SkenarioBalapan::class)) {
                $daftar[Str::kebab(basename($berkas, '.php'))] = $kelas;
            }
        }

        ksort($daftar);

        return $daftar;
    }

    public static function muat(string $nama): SkenarioBalapan
    {
        $daftar = self::daftar();

        if (! isset($daftar[$nama])) {
            throw new \InvalidArgumentException(
                "Skenario '{$nama}' tidak ada. Tersedia: ".implode(', ', array_keys($daftar)),
            );
        }

        /** @var SkenarioBalapan $skenario */
        $skenario = app($daftar[$nama]);

        return $skenario;
    }

    private function jalankan(string $nama): bool
    {
        $skenario = self::muat($nama);
        $jumlah = (int) ($this->option('proses') ?: $skenario->proses());

        $this->newLine();
        $this->line("<options=bold>{$nama}</>: {$skenario->keterangan()}");

        $konteks = $skenario->siapkan();

        // Setiap anak perlu waktu untuk menyalakan Laravel. Semuanya menunggu sampai detik ini,
        // lalu berangkat bersama. Tanpa garis mulai, anak pertama sudah selesai sebelum
        // anak terakhir lahir, dan tidak ada yang berlomba.
        $konteks['mulai'] = microtime(true) + max(4.0, $jumlah * 0.5);
        $muatan = base64_encode(json_encode($konteks, JSON_THROW_ON_ERROR));

        $anak = [];
        for ($nomor = 1; $nomor <= $jumlah; $nomor++) {
            $proses = Process::path(base_path())
                ->timeout(120)
                ->start([PHP_BINARY, 'artisan', 'lapak:balapan-anak', $nama, (string) $nomor, $muatan]);
            $anak[] = $proses;
        }

        $hasil = [];
        foreach ($anak as $proses) {
            $proses = $proses->wait();
            $keluaran = trim($proses->output());
            $hasil[] = $keluaran !== ''
                ? $keluaran
                : 'GALAT '.Str::limit(trim($proses->errorOutput()), 90);
        }

        $rekap = array_count_values($hasil);
        ksort($rekap);
        $this->table(
            ['Hasil', 'Jumlah proses'],
            array_map(null, array_keys($rekap), array_values($rekap)),
        );

        $pelanggaran = $skenario->periksa($konteks, $hasil);

        foreach ($pelanggaran as $pesan) {
            $this->error('PELANGGARAN: '.$pesan);
        }
        if ($pelanggaran === []) {
            $this->info("LULUS: {$jumlah} proses serentak, tidak ada aturan yang dilanggar.");
        }

        return $pelanggaran === [];
    }
}
