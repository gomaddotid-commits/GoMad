<?php
// File: database/migrations/2024_01_01_000008_create_route_pricing_table.php
// Deskripsi: Migration untuk membuat tabel route_pricing

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('origin_stop_id')->constrained('route_stops')->cascadeOnDelete();
            $table->foreignId('destination_stop_id')->constrained('route_stops')->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->timestamps();
            
            $table->unique(['schedule_id', 'origin_stop_id', 'destination_stop_id'], 'route_pricing_unique');
            $table->index('schedule_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_pricing');
    }
};

// End of file