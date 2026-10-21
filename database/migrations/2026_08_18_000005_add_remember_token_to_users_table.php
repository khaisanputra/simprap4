<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom remember_token dibutuhkan oleh fitur "Ingat Saya"
     * (Auth::login dengan remember) pada halaman login.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->rememberToken()->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('remember_token');
        });
    }
};
