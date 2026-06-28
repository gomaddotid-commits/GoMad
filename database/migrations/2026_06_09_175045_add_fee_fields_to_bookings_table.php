<?php
// File: database/migrations/xxxx_xx_xx_add_fee_fields_to_bookings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('base_price', 10, 2)->nullable()->after('total_price')->comment('Harga dasar tiket');
            $table->decimal('service_fee', 10, 2)->default(0)->after('base_price')->comment('Biaya layanan');
            $table->decimal('platform_fee', 10, 2)->default(0)->after('service_fee')->comment('Biaya platform');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('platform_fee')->comment('Jumlah diskon');
            $table->string('payment_method_display')->nullable()->after('status')->comment('Display metode pembayaran');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['base_price', 'service_fee', 'platform_fee', 'discount_amount', 'payment_method_display']);
        });
    }
};