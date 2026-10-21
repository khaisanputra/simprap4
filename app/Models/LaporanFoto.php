<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanFoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'laporan_id',
        'foto',
        'foto_original_name',
        'foto_mime_type',
        'sort_order',
    ];

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(Laporan::class, 'laporan_id');
    }
}
