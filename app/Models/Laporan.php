<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Laporan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tanggal_laporan',
        'teks_laporan',
        'foto',
        'foto_original_name',
        'foto_mime_type',
        'jenis_laporan',
    ];

    protected $casts = [
        'tanggal_laporan' => 'date',
        'jenis_laporan' => 'string',
    ];

    public const JENIS_TEKNISI = 'teknisi';
    public const JENIS_SALES = 'sales';

    public function scopeTeknisi($query)
    {
        // Backward compat: data lama dengan jenis_laporan = NULL dianggap Teknisi.
        // JANGAN ubah data lama di DB; handle di query dengan kondisi aman dan jelas.
        return $query->where(function ($q) {
            $q->where('jenis_laporan', self::JENIS_TEKNISI)
              ->orWhereNull('jenis_laporan');
        });
    }

    public function scopeSales($query)
    {
        return $query->where('jenis_laporan', self::JENIS_SALES);
    }

    public function isSales(): bool
    {
        return $this->jenis_laporan === self::JENIS_SALES;
    }

    public function isTeknisi(): bool
    {
        // Backward compat: NULL (data lama) dianggap Teknisi.
        return $this->jenis_laporan === self::JENIS_TEKNISI || $this->jenis_laporan === null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(LaporanFoto::class, 'laporan_id')->orderBy('sort_order')->orderBy('id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(LaporanSales::class, 'laporan_id');
    }

    /**
     * Alias relasi salesData untuk kemudahan akses.
     */
    public function salesData(): HasMany
    {
        return $this->hasMany(LaporanSales::class, 'laporan_id');
    }

    /**
     * Scope: laporan milik user tertentu.
     */
    public function scopeMilikUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: laporan pada tanggal kalender tertentu (kolom tanggal_laporan).
     * Dipakai untuk pembatasan 1 laporan utama per siswa per hari.
     */
    public function scopePadaTanggal($query, string $tanggal)
    {
        return $query->whereDate('tanggal_laporan', $tanggal);
    }

    /**
     * Cek apakah siswa sudah memiliki laporan utama pada tanggal tertentu.
     * Berlaku untuk SEMUA jenis (teknisi maupun sales) — satu siswa maksimal
     * satu baris laporans per tanggal kalender. Data collect customer
     * (tabel laporan_sales) BUKAN laporan utama dan tidak dihitung di sini.
     *
     * Catatan: tanpa unique constraint DB (data historis memiliki duplikasi),
     * sehingga cek ini bersifat application-level dan dilengkapi lock
     * pessimistic (lockForUpdate) di controller saat transaksi pembuatan.
     */
    public static function sudahAdaPadaTanggal(int $userId, string $tanggal): bool
    {
        return static::query()
            ->where('user_id', $userId)
            ->whereDate('tanggal_laporan', $tanggal)
            ->exists();
    }

    /**
     * Ambil laporan utama siswa pada tanggal tertentu (untuk redirect ke edit).
     */
    public static function laporanPadaTanggal(int $userId, string $tanggal): ?self
    {
        return static::query()
            ->where('user_id', $userId)
            ->whereDate('tanggal_laporan', $tanggal)
            ->orderBy('id')
            ->first();
    }

    /**
     * Accessor: foto URL via controller (tanpa storage:link).
     * Prioritas: foto pertama dari relasi fotos, fallback ke kolom foto lama untuk backward compat.
     */
    public function getFotoUrlAttribute(): ?string
    {
        if ($this->relationLoaded('fotos') && $this->fotos->isNotEmpty()) {
            $first = $this->fotos->first();
            return route('laporan.foto.show', [$this->id, $first->id]);
        }
        if ($this->fotos()->exists()) {
            $first = $this->fotos()->first();
            if ($first) return route('laporan.foto.show', [$this->id, $first->id]);
        }
        if (empty($this->foto)) {
            return null;
        }
        return route('laporan.foto', $this->id);
    }
}
