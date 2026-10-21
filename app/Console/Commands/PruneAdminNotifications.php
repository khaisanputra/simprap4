<?php

namespace App\Console\Commands;

use App\Support\AdminNotificationService;
use Illuminate\Console\Command;

class PruneAdminNotifications extends Command
{
    /**
     * Signature: hapus notifikasi admin > 14 hari berdasarkan created_at.
     * Hanya menyentuh admin_notifications.
     */
    protected $signature = 'notifications:prune {--days=14 : Retensi hari (default 14)}';

    protected $description = 'Hapus notifikasi admin yang lebih tua dari 14 hari (berdasarkan created_at)';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        if ($days < 1) {
            $days = 14;
        }

        $deleted = AdminNotificationService::pruneExpired($days);

        $cutoff = now()->subDays($days)->toDateTimeString();
        $this->info("Prune notifikasi admin selesai: {$deleted} dihapus (retensi {$days} hari, cutoff < {$cutoff}).");
        $this->line('Hanya admin_notifications yang dihapus; attendance/users/laporan tidak tersentuh.');

        return self::SUCCESS;
    }
}
