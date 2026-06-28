<?php
// File: database/migrations/xxxx_xx_xx_create_passenger_transfers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passenger_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('to_schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('from_agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('to_agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->integer('total_passengers')->default(0);
            $table->decimal('transfer_fee_per_passenger', 10, 2)->default(20000);
            $table->decimal('total_transfer_fee', 10, 2)->default(0);
            $table->decimal('total_booking_value', 10, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed', 'cancelled'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('from_schedule_id');
            $table->index('to_schedule_id');
        });

        // Tabel pivot untuk booking yang ditransfer
        Schema::create('passenger_transfer_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passenger_transfer_id')->constrained('passenger_transfers')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['passenger_transfer_id', 'booking_id'], 'transfer_booking_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passenger_transfer_bookings');
        Schema::dropIfExists('passenger_transfers');
    }
};