<?php
// File: database/migrations/2024_01_01_000019_create_settlements_table.php
// Deskripsi: Migration untuk membuat tabel settlements

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_agent_id')->constrained('payment_agents')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('total_transactions')->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('total_commission', 10, 2)->default(0);
            $table->decimal('amount_to_settle', 12, 2)->default(0);
            $table->enum('status', ['pending', 'paid', 'verified', 'overdue'])->default('pending');
            $table->string('payment_method', 50)->nullable();
            $table->string('transaction_id')->nullable();
            $table->json('payment_detail')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            $table->index('payment_agent_id');
            $table->index('status');
            $table->index('period_start');
            $table->index('period_end');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};

// End of file