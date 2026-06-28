<?php
// File: database/migrations/xxxx_xx_xx_add_transfer_fields_to_schedules_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->boolean('allow_passenger_transfer')->default(false)->comment('Izinkan transfer penumpang dari jadwal ini');
            $table->boolean('accept_external_transfer')->default(false)->comment('Terima transfer dari agency lain');
            $table->decimal('transfer_fee_per_passenger', 10, 2)->default(20000)->comment('Biaya transfer per penumpang');
            $table->decimal('max_transfer_fee_percent', 5, 2)->default(20)->comment('Maksimal % biaya transfer dari harga tiket');
            $table->integer('transferred_out_count')->default(0)->comment('Jumlah penumpang yang ditransfer keluar');
            $table->integer('transferred_in_count')->default(0)->comment('Jumlah penumpang yang ditransfer masuk');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn([
                'allow_passenger_transfer',
                'accept_external_transfer',
                'transfer_fee_per_passenger',
                'max_transfer_fee_percent',
                'transferred_out_count',
                'transferred_in_count',
            ]);
        });
    }
};