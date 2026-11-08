<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StatusToko;
use App\Models\Pengguna;
use Database\Factories\TokoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Toko extends Model
{
    /** @use HasFactory<TokoFactory> */
    use HasFactory;

    protected $table = 'toko';

    protected $fillable = ['nama', 'slug', 'kota', 'deskripsi', 'ongkir_rupiah'];

    protected function casts(): array
    {
        return [
            'status' => StatusToko::class,
            'ongkir_rupiah' => 'integer',
        ];
    }

    /** @return BelongsTo<Pengguna, $this> */
    public function pemilik(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pemilik_id');
    }

    /** @return HasMany<Produk, $this> */
    public function produk(): HasMany
    {
        return $this->hasMany(Produk::class, 'toko_id');
    }

    /**
     * Toko milik seorang pengguna. Seluruh pintu "toko saya" berangkat dari scope ini,
     * sehingga pengenal toko tidak pernah diambil dari masukan klien.
     *
     * @param Builder<Toko> $kueri
     */
    public function scopeMilik(Builder $kueri, Pengguna $pengguna): void
    {
        $kueri->where('pemilik_id', $pengguna->id);
    }
}
