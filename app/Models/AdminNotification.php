<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNotification extends Model
{
    protected $table = 'admin_notifications';

    protected $fillable = [
        'type',
        'title',
        'message',
        'student_user_id',
        'student_name',
        'tanggal',
        'reference_id',
        'read_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'read_at' => 'datetime',
        'student_user_id' => 'integer',
        'reference_id' => 'integer',
    ];

    public const TYPE_IZIN = 'izin';
    public const TYPE_DISPENSASI = 'dispensasi';
    public const TYPE_SAKIT = 'sakit';
    public const TYPE_ALPHA = 'alpha';

    public const TYPES = [
        self::TYPE_IZIN,
        self::TYPE_DISPENSASI,
        self::TYPE_SAKIT,
        self::TYPE_ALPHA,
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeByType($query, ?string $type)
    {
        if ($type && in_array($type, self::TYPES, true)) {
            return $query->where('type', $type);
        }
        return $query;
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_IZIN => 'Izin',
            self::TYPE_DISPENSASI => 'Dispensasi',
            self::TYPE_SAKIT => 'Sakit',
            self::TYPE_ALPHA => 'Alpha',
            default => ucfirst($this->type),
        };
    }

    public function getIconAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_IZIN => 'fa-envelope-open-text',
            self::TYPE_DISPENSASI => 'fa-file-circle-check',
            self::TYPE_SAKIT => 'fa-house-medical',
            self::TYPE_ALPHA => 'fa-user-xmark',
            default => 'fa-bell',
        };
    }

    public function getColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_IZIN => '#EAB308',
            self::TYPE_DISPENSASI => '#F97316',
            self::TYPE_SAKIT => '#3B82F6',
            self::TYPE_ALPHA => '#EF4444',
            default => '#64748B',
        };
    }

    public function getBgAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_IZIN => '#FEFCE8',
            self::TYPE_DISPENSASI => '#FFF7ED',
            self::TYPE_SAKIT => '#EFF6FF',
            self::TYPE_ALPHA => '#FEF2F2',
            default => '#F8FAFC',
        };
    }
}
