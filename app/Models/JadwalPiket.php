<?php

namespace App\Models;

use App\Models\DetailJadwal;
use App\Models\HistoriPengerjaan;
use App\Models\KehadiranSiswa;
use App\Models\MasterRegu;
use App\Models\MasterToilet;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JadwalPiket extends Model
{
    protected $table = 'jadwal_piket';

    protected $fillable = ['hari', 'minggu_ke', 'regu_id', 'nama_regu', 'toilet_id', 'status_jadwal'];

    protected function casts(): array
    {
        return ['minggu_ke' => 'integer', 'nama_regu' => 'integer', 'regu_id' => 'integer'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(MasterRegu::class, 'regu_id');
    }

    public function toilet(): BelongsTo
    {
        return $this->belongsTo(MasterToilet::class, 'toilet_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Siswa::class, 'detail_jadwal', 'jadwal_id', 'siswa_id')
            ->withPivot('status_penugasan')
            ->withTimestamps();
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailJadwal::class, 'jadwal_id');
    }

    public function attendances(): HasManyThrough
    {
        return $this->hasManyThrough(KehadiranSiswa::class, DetailJadwal::class, 'jadwal_id', 'siswa_id', 'id', 'siswa_id');
    }

    public function report(): HasOne
    {
        return $this->hasOne(HistoriPengerjaan::class, 'jadwal_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(HistoriPengerjaan::class, 'jadwal_id');
    }
}