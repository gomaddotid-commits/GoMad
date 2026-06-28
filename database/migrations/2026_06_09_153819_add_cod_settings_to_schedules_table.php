<?php
// File: database/migrations/xxxx_xx_xx_add_cod_settings_to_schedules_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->boolean('allow_cod')->default(false)->after('accept_external_transfer')->comment('Izinkan pembayaran COD');
            $table->decimal('cod_min_balance', 12, 2)->default(500000)->after('allow_cod')->comment('Minimal saldo agency untuk COD');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['allow_cod', 'cod_min_balance']);
        });
    }
};