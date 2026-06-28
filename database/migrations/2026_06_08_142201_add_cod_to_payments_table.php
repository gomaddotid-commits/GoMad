<?php
// File: database/migrations/xxxx_xx_xx_add_cod_to_payments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah enum payment_type di payments
        Schema::table('payments', function (Blueprint $table) {
            // Tidak bisa langsung ubah enum di SQLite, jadi kita modifikasi
        });
        
        // Tambahkan field untuk tracking COD
        Schema::table('booking_passengers', function (Blueprint $table) {
            $table->boolean('cod_paid')->default(false)->after('dropped_off_at')->comment('COD: sudah bayar ke driver');
            $table->timestamp('cod_paid_at')->nullable()->after('cod_paid');
            $table->foreignId('cod_confirmed_by')->nullable()->after('cod_paid_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('booking_passengers', function (Blueprint $table) {
            $table->dropForeign(['cod_confirmed_by']);
            $table->dropColumn(['cod_paid', 'cod_paid_at', 'cod_confirmed_by']);
        });
    }
};