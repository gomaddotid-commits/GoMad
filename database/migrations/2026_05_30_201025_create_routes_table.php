<?php
// File: database/migrations/2024_01_01_000004_create_routes_table.php
// Deskripsi: Migration untuk membuat tabel routes

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->string('route_name');
            $table->string('origin_city', 100);
            $table->string('destination_city', 100);
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->integer('estimated_duration')->nullable()->comment('Durasi dalam menit');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('origin_city');
            $table->index('destination_city');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};

// End of file