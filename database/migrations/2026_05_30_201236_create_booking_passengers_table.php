<?php
// File: database/migrations/2024_01_01_000010_create_booking_passengers_table.php
// Deskripsi: Migration untuk membuat tabel booking_passengers

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('passenger_name', 100);
            $table->string('passenger_phone', 20)->nullable();
            $table->decimal('baggage_weight', 5, 2)->nullable();
            $table->integer('seat_number')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('dropped_off_at')->nullable();
            $table->timestamps();
            
            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_passengers');
    }
};

// End of file