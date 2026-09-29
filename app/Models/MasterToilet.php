<?php

namespace App\Models;

use App\Models\HistoriPengerjaan;
use App\Models\JadwalPiket;
use App\Models\MasterGedung;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterToilet extends Model
{
    protected $table = 'master_toilet';

    protected $fillable = ['gedung_id', 'nama_toilet', 'lantai', 'jenis_toilet', 'jumlah_bilik', 'status_toilet'];

    protected function casts(): array
    {
        return ['jumlah_bilik' => 'integer'];
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(MasterGedung::class, 'gedung_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(JadwalPiket::class, 'toilet_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(HistoriPengerjaan::class, 'toilet_id');
    }
}