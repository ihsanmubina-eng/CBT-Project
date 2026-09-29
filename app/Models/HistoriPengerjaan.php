<?php

namespace App\Models;

use App\Models\JadwalPiket;
use App\Models\MasterToilet;
use App\Models\Siswa;
use App\Models\TokenAkses;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoriPengerjaan extends Model
{
    protected $table = 'histori_pengerjaan';

    protected $fillable = [
        'tanggal', 'jadwal_id', 'token_id', 'toilet_id', 'pelapor_id', 'status_kebersihan',
        'catatan', 'status_kunci', 'dikoreksi_oleh', 'alasan_koreksi', 'waktu_koreksi',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'waktu_koreksi' => 'datetime'];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(JadwalPiket::class, 'jadwal_id');
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(TokenAkses::class, 'token_id');
    }

    public function toilet(): BelongsTo
    {
        return $this->belongsTo(MasterToilet::class, 'toilet_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'pelapor_id');
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikoreksi_oleh');
    }
}