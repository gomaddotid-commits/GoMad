<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Matikan foreign key checks dulu
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // 1. Bikin tabel temporary dengan struktur lengkap (ada PRIMARY KEY)
        Schema::create('payments_temp', function (Blueprint $table) {
            $table->id(); // PRIMARY KEY — ini yang penting!
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
            $table->unsignedBigInteger('cash_payment_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('commission', 10, 2)->default(0);
            $table->decimal('agency_revenue', 10, 2)->default(0);
            $table->string('payment_type', 20)->default('midtrans'); // pakai string, bukan enum
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

        // 2. Copy data dari tabel lama ke temporary
        DB::statement("
            INSERT INTO payments_temp (
                id, booking_id, cash_payment_id, amount, commission, 
                agency_revenue, payment_type, status, payment_method, 
                transaction_id, payment_channel, paid_at, expired_at, 
                payment_detail, created_at, updated_at
            ) 
            SELECT 
                id, booking_id, cash_payment_id, amount, commission, 
                agency_revenue, payment_type, status, payment_method, 
                transaction_id, payment_channel, paid_at, expired_at, 
                payment_detail, created_at, updated_at 
            FROM payments
        ");

        // 3. Hapus tabel lama
        Schema::dropIfExists('payments');

        // 4. Rename tabel temporary jadi payments
        Schema::rename('payments_temp', 'payments');

        // Nyalakan kembali foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // Rollback tidak diperlukan karena ini cuma mengubah tipe kolom
        // Data tetap sama
    }
};