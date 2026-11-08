<?php

declare(strict_types=1);

namespace App\Domain\Sesi;

/**
 * Representasi murni kunci sesi: format, pembuatan, penguraian, dan pembandingan.
 * Tidak ada dependensi Laravel; hanya fungsi PHP inti.
 */
final class KunciSesi
{
    private const AWALAN = 'lpk_';

    /** Pola ketat: awalan, 26 karakter ULID (huruf besar+angka), titik, 64 hex kecil. */
    private const POLA = '/^lpk_([0-9A-Z]{26})\.([0-9a-f]{64})$/D';

    public readonly string $pengenal;

    private function __construct(string $pengenal, private readonly string $rahasia)
    {
        $this->pengenal = $pengenal;
    }

    public static function baru(string $pengenal): self
    {
        // random_bytes memakai pembangkit acak kriptografis sistem operasi.
        // Jangan pernah menggantinya dengan rand(), mt_rand(), uniqid(), atau waktu.
        return new self($pengenal, bin2hex(random_bytes(32)));
    }

    /** Mengembalikan null untuk bentuk apa pun yang tidak persis sesuai pola. */
    public static function urai(string $teks): ?self
    {
        if (preg_match(self::POLA, $teks, $bagian) !== 1) {
            return null;
        }

        return new self($bagian[1], $bagian[2]);
    }

    /** Bentuk yang diserahkan ke klien. Hanya dipanggil sekali, saat kunci diterbitkan. */
    public function teks(): string
    {
        return self::AWALAN.$this->pengenal.'.'.$this->rahasia;
    }

    /** Bentuk yang disimpan server. */
    public function hash(): string
    {
        return hash('sha256', $this->rahasia);
    }

    /** hash_equals membandingkan dalam waktu tetap, sehingga lamanya tidak membocorkan isi hash. */
    public function cocok(string $hashTersimpan): bool
    {
        return hash_equals($hashTersimpan, $this->hash());
    }
}
