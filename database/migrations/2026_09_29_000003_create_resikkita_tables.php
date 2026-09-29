<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_gedung', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_gedung', 80)->unique();
            $table->string('deskripsi', 180)->nullable();
            $table->timestamps();
        });

        Schema::create('master_toilet', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('gedung_id')->constrained('master_gedung')->restrictOnDelete();
            $table->string('nama_toilet', 80);
            $table->string('lantai', 30);
            $table->string('jenis_toilet', 30);
            $table->unsignedSmallInteger('jumlah_bilik')->default(1);
            $table->string('status_toilet', 20)->default('Aktif');
            $table->timestamps();
            $table->unique(['gedung_id', 'nama_toilet', 'lantai']);
        });

        Schema::create('siswa', function (Blueprint $table): void {
            $table->id();
            $table->string('nis', 30)->unique();
            $table->string('nama_lengkap', 100);
            $table->string('rombel', 40);
            $table->string('status_siswa', 20)->default('Aktif');
            $table->date('tanggal_mutasi')->nullable();
            $table->string('jenis_mutasi', 40)->nullable();
            $table->text('catatan_mutasi')->nullable();
            $table->timestamps();
            $table->index(['nama_lengkap', 'rombel']);
        });

        Schema::create('token_akses', function (Blueprint $table): void {
            $table->id();
            $table->string('kode_token', 30)->unique();
            $table->date('tanggal');
            $table->unsignedTinyInteger('minggu_ke');
            $table->time('berlaku_mulai');
            $table->time('berlaku_sampai');
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->default('Aktif');
            $table->timestamps();
            $table->index(['tanggal', 'status']);
        });

        Schema::create('jadwal_piket', function (Blueprint $table): void {
            $table->id();
            $table->string('hari', 12);
            $table->unsignedTinyInteger('minggu_ke');
            $table->unsignedTinyInteger('nama_regu');
            $table->foreignId('toilet_id')->constrained('master_toilet')->restrictOnDelete();
            $table->string('status_jadwal', 20)->default('Aktif');
            $table->timestamps();
            $table->index(['status_jadwal', 'hari', 'minggu_ke', 'nama_regu']);
        });

        Schema::create('detail_jadwal', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('jadwal_piket')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->restrictOnDelete();
            $table->string('status_penugasan', 20)->default('Aktif');
            $table->timestamps();
            $table->unique(['jadwal_id', 'siswa_id']);
        });

        Schema::create('kehadiran_siswa', function (Blueprint $table): void {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('siswa_id')->constrained('siswa')->restrictOnDelete();
            $table->string('status_kehadiran', 20)->default('Hadir');
            $table->timestamps();
            $table->unique(['tanggal', 'siswa_id']);
        });

        Schema::create('histori_pengerjaan', function (Blueprint $table): void {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('jadwal_id')->constrained('jadwal_piket')->restrictOnDelete();
            $table->foreignId('token_id')->constrained('token_akses')->restrictOnDelete();
            $table->foreignId('toilet_id')->constrained('master_toilet')->restrictOnDelete();
            $table->foreignId('pelapor_id')->nullable()->constrained('siswa')->nullOnDelete();
            $table->string('status_kebersihan', 40);
            $table->text('catatan')->nullable();
            $table->string('status_kunci', 20)->default('Terkunci');
            $table->foreignId('dikoreksi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('alasan_koreksi')->nullable();
            $table->dateTime('waktu_koreksi')->nullable();
            $table->timestamps();
            $table->unique('jadwal_id');
            $table->index(['tanggal', 'status_kebersihan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('histori_pengerjaan');
        Schema::dropIfExists('kehadiran_siswa');
        Schema::dropIfExists('detail_jadwal');
        Schema::dropIfExists('jadwal_piket');
        Schema::dropIfExists('token_akses');
        Schema::dropIfExists('siswa');
        Schema::dropIfExists('master_toilet');
        Schema::dropIfExists('master_gedung');
    }
};