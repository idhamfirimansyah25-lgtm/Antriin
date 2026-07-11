<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Layanan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_layanan',
        'kode_prefix',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function lokets(): HasMany
    {
        return $this->hasMany(Loket::class);
    }

    public function antrians(): HasMany
    {
        return $this->hasMany(Antrian::class);
    }
}
