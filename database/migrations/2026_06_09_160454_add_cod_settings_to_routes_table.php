<?php
// File: database/migrations/xxxx_xx_xx_add_cod_settings_to_routes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->decimal('max_price', 10, 2)->nullable()->after('estimated_duration')->comment('Batas maksimal harga tiket');
            $table->decimal('cod_min_deposit', 12, 2)->default(500000)->after('max_price')->comment('Minimal deposit agency untuk COD di rute ini');
            $table->boolean('cod_available')->default(false)->after('cod_min_deposit')->comment('Apakah COD tersedia untuk rute ini');
        });
    }

    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn(['max_price', 'cod_min_deposit', 'cod_available']);
        });
    }
};