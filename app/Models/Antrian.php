<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Antrian extends Model
{
    use HasFactory;

    protected $fillable = [
        'layanan_id',
        'loket_id',
        'nomor_antrian',
        'status',
        'waktu_ambil',
        'waktu_dipanggil',
        'waktu_selesai',
        'tanggal',
    ];

    protected $casts = [
        'waktu_ambil' => 'datetime',
        'waktu_dipanggil' => 'datetime',
        'waktu_selesai' => 'datetime',
        'tanggal' => 'date',
    ];

    public function layanan(): BelongsTo
    {
        return $this->belongsTo(Layanan::class);
    }

    public function loket(): BelongsTo
    {
        return $this->belongsTo(Loket::class);
    }
}
