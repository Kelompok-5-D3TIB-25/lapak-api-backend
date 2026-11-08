<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NamaIzin;
use App\Enums\NamaPeran;
use App\Enums\StatusAkun;
use App\Services\PemeriksaIzin;
use Database\Factories\PenggunaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

final class Pengguna extends Authenticatable
{
    /** @use HasFactory<PenggunaFactory> */
    use HasFactory;

    protected $table = 'pengguna';

    // status TIDAK ada di sini: klien tidak boleh menentukan status akunnya sendiri.
    protected $fillable = ['nama', 'surel', 'telepon', 'kata_sandi'];

    protected $hidden = ['kata_sandi'];

    protected $authPasswordName = 'kata_sandi';

    protected function casts(): array
    {
        return [
            'kata_sandi' => 'hashed',
            'status' => StatusAkun::class,
        ];
    }

    /**
     * Peran yang dipegang. Relasi ini hanya untuk MEMBACA.
     * Memberi dan mencabut peran selalu lewat App\Services\PengelolaPeran.
     *
     * @return BelongsToMany<Peran, $this>
     */
    public function peran(): BelongsToMany
    {
        return $this->belongsToMany(Peran::class, 'pengguna_peran', 'pengguna_id', 'peran_id')
            ->withPivot('diberikan_oleh', 'created_at');
    }

    public function punyaPeran(NamaPeran $peran): bool
    {
        return $this->peran()->where('peran.nama', $peran->value)->exists();
    }

    public function punyaIzin(NamaIzin $izin): bool
    {
        return app(PemeriksaIzin::class)->punya($this, $izin);
    }
}
