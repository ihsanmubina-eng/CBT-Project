<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('histori_pengerjaan', function (Blueprint $table): void {
            $table->dropForeign(['jadwal_id']);
            $table->dropUnique(['jadwal_id']);
            $table->foreign('jadwal_id')->references('id')->on('jadwal_piket')->restrictOnDelete();
            $table->unique(['jadwal_id', 'pelapor_id']);
            $table->string('status_kehadiran', 20)->default('Hadir')->after('status_kebersihan');
        });
    }

    public function down(): void
    {
        Schema::table('histori_pengerjaan', function (Blueprint $table): void {
            $table->dropColumn('status_kehadiran');
            $table->dropForeign(['jadwal_id']);
            $table->dropUnique(['jadwal_id', 'pelapor_id']);
            $table->foreign('jadwal_id')->references('id')->on('jadwal_piket')->restrictOnDelete();
            $table->unique('jadwal_id');
        });
    }
};
