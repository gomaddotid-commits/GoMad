<?php
// File: database/migrations/2024_01_01_000005_create_route_stops_table.php
// Deskripsi: Migration untuk membuat tabel route_stops

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->string('city_name', 100);
            $table->integer('stop_order');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('distance_from_origin', 8, 2)->nullable();
            $table->timestamps();
            
            $table->unique(['route_id', 'stop_order'], 'route_stop_unique');
            $table->index('city_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_stops');
    }
};

// End of file