<?php

namespace App\Models;

use App\Models\Siswa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KehadiranSiswa extends Model
{
    protected $table = 'kehadiran_siswa';

    protected $fillable = ['tanggal', 'siswa_id', 'status_kehadiran'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}