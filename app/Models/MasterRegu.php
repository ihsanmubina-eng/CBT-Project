<?php

namespace App\Models;

use App\Models\JadwalPiket;
use App\Models\MasterGedung;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterRegu extends Model
{
    protected $table = 'master_regu';

    protected $fillable = [
        'nama_regu',
        'gedung_id',
        'ketua_siswa_id',
        'keterangan',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(MasterGedung::class, 'gedung_id');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'ketua_siswa_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(JadwalPiket::class, 'regu_id');
    }
}
