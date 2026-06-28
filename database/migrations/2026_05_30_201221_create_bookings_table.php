<?php
// File: database/migrations/2024_01_01_000009_create_bookings_table.php
// Deskripsi: Migration untuk membuat tabel bookings

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique()->comment('Format: GM-YYYYMMDD-XXXX');
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('origin_stop_id')->constrained('route_stops')->cascadeOnDelete();
            $table->foreignId('destination_stop_id')->constrained('route_stops')->cascadeOnDelete();
            $table->foreignId('route_pricing_id')->nullable()->constrained('route_pricing')->nullOnDelete();
            $table->text('pickup_address');
            $table->string('pickup_maps_link')->nullable();
            $table->decimal('pickup_latitude', 10, 7)->nullable();
            $table->decimal('pickup_longitude', 10, 7)->nullable();
            $table->text('destination_address');
            $table->string('destination_maps_link')->nullable();
            $table->decimal('destination_latitude', 10, 7)->nullable();
            $table->decimal('destination_longitude', 10, 7)->nullable();
            $table->integer('total_passengers')->default(1);
            $table->decimal('total_price', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'paid', 'cancelled', 'completed', 'on_going'])->default('pending');
            $table->text('special_notes')->nullable();
            $table->string('e_ticket_url')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('booking_code');
            $table->index('schedule_id');
            $table->index('customer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};

// End of file