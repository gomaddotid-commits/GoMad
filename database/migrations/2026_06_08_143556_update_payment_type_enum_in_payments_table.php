<?php
// File: database/migrations/xxxx_xx_xx_update_payment_type_enum_in_payments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite tidak support modify enum, jadi kita perlu recreate
        // Buat tabel temporary
        DB::statement('CREATE TABLE payments_temp AS SELECT * FROM payments');
        
        // Drop tabel lama
        Schema::drop('payments');
        
        // Buat tabel baru dengan enum yang diupdate
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
            $table->unsignedBigInteger('cash_payment_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('commission', 10, 2)->default(0);
            $table->decimal('agency_revenue', 10, 2)->default(0);
            $table->string('payment_type', 20)->default('midtrans'); // Pakai string instead of enum
            $table->string('status', 30)->default('pending');
            $table->string('payment_method', 50)->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('payment_channel', 50)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->json('payment_detail')->nullable();
            $table->timestamps();
            
            $table->index('payment_type');
            $table->index('status');
            $table->index('transaction_id');
        });
        
        // Copy data dari tabel temporary
        DB::statement("INSERT INTO payments SELECT * FROM payments_temp");
        
        // Drop tabel temporary
        Schema::drop('payments_temp');
    }

    public function down(): void
    {
        // Tidak perlu rollback
    }
};