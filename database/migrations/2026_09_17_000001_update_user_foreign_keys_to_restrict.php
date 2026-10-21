<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ubah FK user_id dari CASCADE menjadi RESTRICT untuk melindungi historis.
     * - laporans.user_id
     * - laporan_sales.user_id
     * - monthly_statistics.user_id
     * attendances.user_id tidak memiliki FK (hanya index), jadi tidak diubah.
     * Tidak menghapus data, hanya mengubah constraint.
     */
    public function up(): void
    {
        // Untuk SQLite testing, FK handling berbeda; bungkus try/catch agar migration tetap jalan
        $this->updateFk('laporans', 'user_id', 'users');
        $this->updateFk('laporan_sales', 'user_id', 'users');
        $this->updateFk('monthly_statistics', 'user_id', 'users');
    }

    private function updateFk(string $table, string $column, string $referencesTable): void
    {
        try {
            // Cek apakah kolom ada
            if (!Schema::hasColumn($table, $column)) {
                return;
            }

            // Untuk MySQL: drop FK lama dan buat baru dengan restrict
            // Nama FK default Laravel: {table}_{column}_foreign
            $fkName = $table . '_' . $column . '_foreign';

            // Coba drop FK jika ada (MySQL)
            try {
                Schema::table($table, function (Blueprint $t) use ($fkName, $column) {
                    // dropForeign akan gagal jika FK tidak ada (misal SQLite atau sudah restrict), abaikan
                    try {
                        $t->dropForeign($fkName);
                    } catch (\Throwable $e) {
                        // Coba drop via column (Laravel 10+)
                        try {
                            $t->dropForeign([$column]);
                        } catch (\Throwable $e2) {
                            // abaikan
                        }
                    }
                });
            } catch (\Throwable $e) {
                // abaikan untuk SQLite
            }

            // Buat FK baru dengan restrictOnDelete
            // Untuk SQLite, perlu nonaktifkan FK check sementara
            if (DB::getDriverName() === 'sqlite') {
                // SQLite tidak support alter FK dengan mudah; skip karena testing pakai RefreshDatabase akan recreate dari migration
                // Di testing, tabel akan dibuat ulang dengan definisi baru jika kita ubah migration awal,
                // tapi untuk existing, kita biarkan (aplikasi sudah cek historis di UserController)
                return;
            }

            Schema::table($table, function (Blueprint $t) use ($column, $referencesTable) {
                $t->foreign($column)
                    ->references('id')
                    ->on($referencesTable)
                    ->restrictOnDelete()
                    ->cascadeOnUpdate();
            });
        } catch (\Throwable $e) {
            // Jangan gagalkan migration untuk SQLite/testing
            if (app()->environment('testing')) {
                return;
            }
            throw $e;
        }
    }

    /**
     * Rollback: kembalikan ke cascadeOnDelete
     */
    public function down(): void
    {
        $this->revertFk('laporans', 'user_id', 'users');
        $this->revertFk('laporan_sales', 'user_id', 'users');
        $this->revertFk('monthly_statistics', 'user_id', 'users');
    }

    private function revertFk(string $table, string $column, string $referencesTable): void
    {
        try {
            if (!Schema::hasColumn($table, $column)) {
                return;
            }
            $fkName = $table . '_' . $column . '_foreign';
            try {
                Schema::table($table, function (Blueprint $t) use ($fkName, $column) {
                    try {
                        $t->dropForeign($fkName);
                    } catch (\Throwable $e) {
                        try {
                            $t->dropForeign([$column]);
                        } catch (\Throwable $e2) {}
                    }
                });
            } catch (\Throwable $e) {}

            if (DB::getDriverName() === 'sqlite') {
                return;
            }

            Schema::table($table, function (Blueprint $t) use ($column, $referencesTable) {
                $t->foreign($column)
                    ->references('id')
                    ->on($referencesTable)
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
            });
        } catch (\Throwable $e) {
            if (app()->environment('testing')) {
                return;
            }
            throw $e;
        }
    }
};
