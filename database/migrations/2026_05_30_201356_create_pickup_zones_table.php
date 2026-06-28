<?php
// File: database/migrations/2024_01_01_000014_create_pickup_zones_table.php
// Deskripsi: Migration untuk membuat tabel pickup_zones

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('zone_name', 100);
            $table->string('kecamatan', 100)->nullable();
            $table->decimal('additional_fee', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('agency_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_zones');
    }
};

// End of file