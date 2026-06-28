<?php
// File: database/migrations/2024_01_01_000003_create_vehicles_table.php
// Deskripsi: Migration untuk membuat tabel vehicles

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('plate_number')->unique();
            $table->string('brand', 50);
            $table->string('model', 50);
            $table->year('year')->nullable();
            $table->integer('capacity')->default(8);
            $table->enum('type', ['economy', 'premium'])->default('economy');
            $table->string('vehicle_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('agency_id');
            $table->index('type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};

// End of file