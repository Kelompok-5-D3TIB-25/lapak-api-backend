<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ButirPesananFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ButirPesanan extends Model
{
    /** @use HasFactory<ButirPesananFactory> */
    use HasFactory;

    protected $table = 'butir_pesanan';

    protected $fillable = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'harga_rupiah' => 'integer',
            'kuantitas' => 'integer',
            'subtotal_rupiah' => 'integer',
        ];
    }

    /** @return BelongsTo<Pesanan, $this> */
    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id');
    }
}
