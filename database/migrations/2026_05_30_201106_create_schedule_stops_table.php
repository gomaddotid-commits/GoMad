<?php
// File: database/migrations/2024_01_01_000007_create_schedule_stops_table.php
// Deskripsi: Migration untuk membuat tabel schedule_stops

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('route_stop_id')->constrained('route_stops')->cascadeOnDelete();
            $table->boolean('is_pickup_available')->default(true);
            $table->boolean('is_dropoff_available')->default(false);
            $table->time('estimated_time')->nullable();
            $table->timestamps();
            
            $table->unique(['schedule_id', 'route_stop_id'], 'schedule_stop_unique');
            $table->index('is_pickup_available');
            $table->index('is_dropoff_available');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_stops');
    }
};

// End of file