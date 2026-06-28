<?php
// File: database/migrations/2024_01_01_000012_create_cash_payments_table.php
// Deskripsi: Migration untuk membuat tabel cash_payments

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('payment_agent_id')->nullable()->constrained('payment_agents')->nullOnDelete();
            $table->string('payment_code')->unique()->comment('Format: WM-YYYYMMDD-XXXXXX');
            $table->decimal('amount', 10, 2);
            $table->decimal('agent_commission', 10, 2)->default(0);
            $table->decimal('platform_commission', 10, 2)->default(0);
            $table->enum('status', ['pending', 'confirmed', 'expired', 'settled'])->default('pending');
            $table->foreignId('settlement_id')->nullable()->constrained('settlements')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();
            
            $table->index('payment_code');
            $table->index('status');
            $table->index('payment_agent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_payments');
    }
};

// End of file