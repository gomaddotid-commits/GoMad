<?php
// File: database/migrations/2026_05_30_201254_create_payments_table.php
// Deskripsi: Migration untuk membuat tabel payments (FIXED)

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
            // HAPUS FK ke cash_payments dulu
            $table->unsignedBigInteger('cash_payment_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('commission', 10, 2)->default(0);
            $table->decimal('agency_revenue', 10, 2)->default(0);
            $table->string('payment_type', 20)->default('midtrans');
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
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

// End of file