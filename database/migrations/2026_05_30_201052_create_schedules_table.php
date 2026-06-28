<?php
// File: database/migrations/2024_01_01_000006_create_schedules_table.php
// Deskripsi: Migration untuk membuat tabel schedules

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('departure_date');
            $table->time('departure_time');
            $table->enum('travel_class', ['economy', 'premium', 'charter', 'rental'])->default('economy');
            $table->integer('max_overload')->default(0);
            $table->decimal('price_per_seat', 10, 2)->comment('Harga Dasar (Asal Utama ke Tujuan Utama)');
            $table->decimal('baggage_limit_kg', 5, 2)->default(20.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('agency_id');
            $table->index('departure_date');
            $table->index('travel_class');
            $table->index('is_active');
            $table->index('driver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};

// End of file