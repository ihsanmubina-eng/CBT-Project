<?php

namespace App\Models;

use App\Models\MasterToilet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterGedung extends Model
{
    protected $table = 'master_gedung';

    protected $fillable = ['nama_gedung', 'deskripsi'];

    public function toilets(): HasMany
    {
        return $this->hasMany(MasterToilet::class, 'gedung_id');
    }
}