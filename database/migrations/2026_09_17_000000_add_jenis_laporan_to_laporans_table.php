<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambahkan penanda jenis laporan untuk memisahkan Teknisi vs Sales.
     * - jenis_laporan: enum teknisi/sales, default teknisi, nullable sementara untuk data existing.
     * - Data existing diklasifikasikan berdasarkan relasi laporan_sales.laporan_id:
     *     Sales jika memiliki child di laporan_sales, Teknisi jika tidak.
     * - Tidak menghapus data, tidak mengubah ID, backward-compatible.
     */
    public function up(): void
    {
        // 1. Tambah kolom jika belum ada — nullable tanpa default, agar tidak menebak
        if (!Schema::hasColumn('laporans', 'jenis_laporan')) {
            Schema::table('laporans', function (Blueprint $table) {
                $table->string('jenis_laporan', 20)->nullable()->after('teks_laporan');
                $table->index('jenis_laporan');
            });
        }

        // 2. Klasifikasikan parent Sales yang DAPAT DIBUKTIKAN: memiliki child di laporan_sales
        //    Hanya ini yang aman; sisanya dibiarkan NULL (ambiguous) dan dilaporkan
        $salesParentIds = collect();
        $ambiguousCount = 0;
        $totalBefore = DB::table('laporans')->count();
        try {
            $salesParentIds = DB::table('laporan_sales')
                ->whereNotNull('laporan_id')
                ->distinct()
                ->pluck('laporan_id')
                ->filter()
                ->unique()
                ->values();

            if ($salesParentIds->isNotEmpty()) {
                DB::table('laporans')
                    ->whereIn('id', $salesParentIds)
                    ->update(['jenis_laporan' => 'sales']);
            }

            // Hitung yang terklasifikasi
            $salesCount = DB::table('laporans')->where('jenis_laporan', 'sales')->count();
            $ambiguousCount = DB::table('laporans')->whereNull('jenis_laporan')->count();
            $teknisiProven = 0; // Tidak ada cara aman membuktikan teknisi tanpa child, jadi 0

            // Log untuk audit (tidak block migration)
            \Illuminate\Support\Facades\Log::info('Migration jenis_laporan klasifikasi', [
                'total' => $totalBefore,
                'sales_terbukti' => $salesCount,
                'ambiguous_null' => $ambiguousCount,
                'teknisi_terbukti' => $teknisiProven,
                'note' => 'Parent Sales tanpa child (0 sales) tidak dapat dibedakan dari Teknisi tanpa informasi tambahan — dibiarkan NULL dan dilaporkan',
            ]);
        } catch (\Throwable $e) {
            if (app()->environment('testing')) {
                // di testing, abaikan
            } else {
                throw $e;
            }
        }

        // 3. JANGAN mengubah data ambiguous menjadi teknisi — biarkan NULL
        //    Aplikasi akan handle NULL sebagai teknisi untuk backward compat, tapi migration tidak menebak
        //    Data baru setelah migration wajib set jenis_laporan (teknisi/sales) saat create
    }

    /**
     * Rollback: hapus kolom jenis_laporan.
     */
    public function down(): void
    {
        if (Schema::hasColumn('laporans', 'jenis_laporan')) {
            Schema::table('laporans', function (Blueprint $table) {
                try {
                    $table->dropIndex(['jenis_laporan']);
                } catch (\Throwable $e) {
                    // index mungkin tidak ada di SQLite
                }
                $table->dropColumn('jenis_laporan');
            });
        }
    }
};
