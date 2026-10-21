<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class MonthlyStatistic extends Model
{
    use HasFactory;

    protected $table = 'monthly_statistics';

    protected $fillable = [
        'user_id',
        'bulan',
        'tahun',
        'jumlah_masuk',
        'jumlah_pulang',
        'jumlah_izin',
        'jumlah_sakit',
        'jumlah_dispensasi',
        'jumlah_telat',
        'jumlah_tidak_masuk',
        'persentase_kehadiran',
        // field baru sederhana
        'jumlah_hadir',
        'jumlah_alpha',
        'jumlah_laporan',
    ];

    protected $casts = [
        'bulan' => 'integer',
        'tahun' => 'integer',
        'jumlah_masuk' => 'integer',
        'jumlah_pulang' => 'integer',
        'jumlah_izin' => 'integer',
        'jumlah_sakit' => 'integer',
        'jumlah_dispensasi' => 'integer',
        'jumlah_telat' => 'integer',
        'jumlah_tidak_masuk' => 'integer',
        'persentase_kehadiran' => 'integer',
        'jumlah_hadir' => 'integer',
        'jumlah_alpha' => 'integer',
        'jumlah_laporan' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Nama bulan Indonesia untuk label.
     */
    public static function namaBulan(int $bulan): string
    {
        $map = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
        return $map[$bulan] ?? (string)$bulan;
    }

    public function getLabelBulanTahunAttribute(): string
    {
        return static::namaBulan((int)$this->bulan) . ' ' . $this->tahun;
    }

    /**
     * Scope arsip milik user tertentu.
     */
    public function scopeUntukUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePadaPeriode($query, int $bulan, int $tahun)
    {
        return $query->where('bulan', $bulan)->where('tahun', $tahun);
    }

    public function scopeTerbaru($query)
    {
        return $query->orderByDesc('tahun')->orderByDesc('bulan');
    }
}
