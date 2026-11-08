<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('toko', function (Blueprint $tabel) {
            $tabel->foreignId('dimoderasi_oleh')->nullable()->constrained('pengguna')->nullOnDelete();
            $tabel->timestamp('dimoderasi_pada')->nullable();
            $tabel->string('catatan_moderasi', 300)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('toko', function (Blueprint $tabel) {
            $tabel->dropConstrainedForeignId('dimoderasi_oleh');
            $tabel->dropColumn(['dimoderasi_pada', 'catatan_moderasi']);
        });
    }
};
