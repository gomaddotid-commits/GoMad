<?php
// File: database/migrations/2024_01_01_000015_create_payment_agents_table.php
// Deskripsi: Migration untuk membuat tabel payment_agents

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('agent_name', 100);
            $table->string('owner_name', 100);
            $table->string('owner_phone', 20);
            $table->string('guard_name', 100)->nullable();
            $table->string('guard_phone', 20)->nullable();
            $table->text('address');
            $table->string('kecamatan', 100)->nullable();
            $table->string('maps_link')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('photo_warung')->nullable();
            $table->string('photo_ktp_owner')->nullable();
            $table->string('photo_ktp_guard')->nullable();
            $table->string('pin', 100);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->decimal('commission_rate', 4, 2)->default(2.00);
            $table->integer('total_transactions')->default(0);
            $table->decimal('total_commission', 10, 2)->default(0);
            $table->decimal('balance_to_settle', 10, 2)->default(0);
            $table->timestamp('last_settlement_at')->nullable();
            $table->timestamps();
            
            $table->index('is_active');
            $table->index('is_verified');
            $table->index('kecamatan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_agents');
    }
};

// End of file