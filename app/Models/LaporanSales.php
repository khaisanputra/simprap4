<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanSales extends Model
{
    use HasFactory;

    protected $table = 'laporan_sales';

    protected $fillable = [
        'user_id',
        'laporan_id',
        'tanggal',
        'nama_customer',
        'no_telp',
        'alamat_customer',
        'provider',
        'kelebihan',
        'kekurangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias collector sama dengan user (siswa yang login).
     */
    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(Laporan::class, 'laporan_id');
    }

    /**
     * Daftar provider valid (20 pilihan, jangan diubah).
     */
    public const PROVIDERS = [
        'Lintas Data',
        'Biznet',
        'RT RW',
        'Inalite',
        'Indihome',
        'Cabangan',
        'Wifi Pak Sunarto',
        'Iprime',
        'Iconnet',
        'Tridata',
        'Speedy',
        'Bagongnet',
        'Smartnet',
        'Wifa',
        'Starlite',
        'Tidak menggunakan layanan internet',
        'Pakai tidak tau namanya',
        'PT Sari Perkasa',
        'Indibis',
        'Azafanet',
    ];

    public function scopeMilikUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
