<?php

namespace App\Models;

use App\Models\JadwalPiket;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailJadwal extends Model
{
    protected $table = 'detail_jadwal';

    protected $fillable = ['jadwal_id', 'siswa_id', 'status_penugasan'];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(JadwalPiket::class, 'jadwal_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}