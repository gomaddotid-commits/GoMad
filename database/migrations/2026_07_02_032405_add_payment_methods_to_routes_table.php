<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->string('payment_methods')->nullable()->after('cod_available')
                ->comment('Metode pembayaran yang tersedia: midtrans,cash,cod (null = semua)');
        });
    }

    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn('payment_methods');
        });
    }
};