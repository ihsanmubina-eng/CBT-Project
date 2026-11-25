<?php

namespace App\Models;

use App\Models\HistoriPengerjaan;
use App\Models\TokenAkses;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['nama_lengkap', 'username', 'password', 'role', 'foto'];

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

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->foto && Storage::disk('public')->exists($this->foto)) {
            return asset('storage/' . $this->foto);
        }

        return null;
    }

    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->nama_lengkap));

        return collect($parts)->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
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
