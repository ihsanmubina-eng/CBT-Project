<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_regu', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_regu', 100);
            $table->foreignId('gedung_id')->constrained('master_gedung')->restrictOnDelete();
            $table->foreignId('ketua_siswa_id')->nullable()->constrained('siswa')->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::table('jadwal_piket', function (Blueprint $table): void {
            $table->foreignId('regu_id')->nullable()->after('minggu_ke')->constrained('master_regu')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('jadwal_piket', function (Blueprint $table): void {
            $table->dropForeign(['regu_id']);
            $table->dropColumn('regu_id');
        });

        Schema::dropIfExists('master_regu');
    }
};
