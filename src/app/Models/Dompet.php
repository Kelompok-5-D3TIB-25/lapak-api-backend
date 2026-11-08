<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JenisDompet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Dompet extends Model
{
    protected $table = 'dompet';

    // Sengaja kosong: dompet hanya dibuat dan diubah oleh BukuBesar, tidak pernah dari masukan klien.
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'jenis' => JenisDompet::class,
            'saldo_rupiah' => 'integer',
        ];
    }

    /**
     * Null untuk dompet sistem (eksternal, escrow, pendapatan).
     *
     * @return BelongsTo<Pengguna, $this>
     */
    public function pemilik(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }

    /** @return HasMany<MutasiSaldo, $this> */
    public function mutasi(): HasMany
    {
        return $this->hasMany(MutasiSaldo::class, 'dompet_id');
    }
}
