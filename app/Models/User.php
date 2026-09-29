<?php

namespace App\Models;

use App\Models\HistoriPengerjaan;
use App\Models\TokenAkses;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['nama_lengkap', 'username', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(TokenAkses::class, 'dibuat_oleh');
    }

    public function koreksiLaporan(): HasMany
    {
        return $this->hasMany(HistoriPengerjaan::class, 'dikoreksi_oleh');
    }
}
