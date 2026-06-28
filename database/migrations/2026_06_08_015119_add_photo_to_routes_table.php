<?php
// File: database/migrations/xxxx_xx_xx_add_photo_to_routes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('is_active');
            $table->text('description')->nullable()->after('photo');
        });
    }

    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn(['photo', 'description']);
        });
    }
};