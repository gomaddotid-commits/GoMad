<?php
// File: database/migrations/xxxx_xx_xx_add_status_timestamps_to_schedules_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('is_active')->comment('Waktu agency klik Mulai');
            $table->timestamp('finished_at')->nullable()->after('started_at')->comment('Waktu perjalanan selesai');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'finished_at']);
        });
    }
};