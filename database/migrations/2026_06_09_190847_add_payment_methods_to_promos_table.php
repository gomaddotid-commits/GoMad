<?php
// File: database/migrations/xxxx_xx_xx_add_payment_methods_to_promos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->string('applicable_payment_methods')->nullable()->after('travel_class')
                ->comment('Metode pembayaran yang berlaku: midtrans,cash,cod (null = semua)');
        });
    }

    public function down(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->dropColumn('applicable_payment_methods');
        });
    }
};