<?php

namespace App\Models;

use App\Models\HistoriPengerjaan;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TokenAkses extends Model
{
    protected $table = 'token_akses';

    protected $fillable = ['kode_token', 'tanggal', 'minggu_ke', 'berlaku_mulai', 'berlaku_sampai', 'dibuat_oleh', 'status'];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'minggu_ke' => 'integer'];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(HistoriPengerjaan::class, 'token_id');
    }
}