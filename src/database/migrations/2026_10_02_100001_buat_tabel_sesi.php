<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesi', function (Blueprint $tabel) {
            $tabel->id();
            // Bagian kunci yang boleh terlihat. Dipakai mencari baris dan menyebut sesi di URL.
            $tabel->char('pengenal', 26)->unique();
            $tabel->foreignId('pengguna_id')->constrained('pengguna')->cascadeOnDelete();
            // SHA-256 dari bagian rahasia. Rahasianya sendiri tidak pernah disimpan (LP-14).
            $tabel->char('hash_kunci', 64);
            $tabel->string('nama_perangkat', 60);
            $tabel->string('ip', 45)->nullable();
            $tabel->string('agen_pengguna', 255)->nullable();
            // Kapan kata sandi terakhir dibuktikan pada sesi ini (LP-15).
            $tabel->timestamp('dikonfirmasi_pada')->nullable();
            $tabel->timestamp('terakhir_dipakai_pada');
            $tabel->timestamp('kedaluwarsa_pada');
            $tabel->timestamp('dicabut_pada')->nullable();
            $tabel->timestamps();

            $tabel->index(['pengguna_id', 'dicabut_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesi');
    }
};
