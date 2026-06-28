<?php
// File: database/migrations/2024_01_01_000001_create_agencies_table.php
// Deskripsi: Migration untuk membuat tabel agencies

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('agency_name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('business_license')->nullable();
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->year('founded_year')->nullable();
            $table->integer('fleet_size')->default(0);
            $table->json('services')->nullable();
            $table->json('social_media')->nullable();
            $table->json('business_hours')->nullable();
            $table->json('zone_coverage')->nullable();
            $table->string('contact_person', 100)->nullable();
            $table->string('contact_alternate', 20)->nullable();
            $table->string('email_alternate')->nullable();
            $table->json('gallery')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('total_bookings')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('slug');
            $table->index('is_verified');
            $table->index('rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};

// End of file