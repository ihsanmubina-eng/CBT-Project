<?php

namespace App\Models;

use App\Models\DetailJadwal;
use App\Models\HistoriPengerjaan;
use App\Models\JadwalPiket;
use App\Models\KehadiranSiswa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Siswa extends Model
{
    protected $table = 'siswa';

    protected $fillable = ['nis', 'nama_lengkap', 'rombel', 'status_siswa', 'tanggal_mutasi', 'jenis_mutasi', 'catatan_mutasi'];

    protected function casts(): array
    {
        return ['tanggal_mutasi' => 'date'];
    }

    public function jadwal(): BelongsToMany
    {
        return $this->belongsToMany(JadwalPiket::class, 'detail_jadwal', 'siswa_id', 'jadwal_id')
            ->withPivot('status_penugasan')
            ->withTimestamps();
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailJadwal::class, 'siswa_id');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(KehadiranSiswa::class, 'siswa_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(HistoriPengerjaan::class, 'pelapor_id');
    }
}