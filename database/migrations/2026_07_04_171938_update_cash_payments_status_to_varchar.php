<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah kolom status di cash_payments dari ENUM ke VARCHAR
        Schema::table('cash_payments', function (Blueprint $table) {
            // Ubah jadi VARCHAR(30) — cukup untuk semua status baru
            $table->string('status', 30)->default('pending')->change();
        });
    }

    public function down(): void
    {
        // Kembalikan ke ENUM jika perlu rollback
        // Ini tidak bisa dikembalikan dengan mudah, jadi biarkan saja
    }
};