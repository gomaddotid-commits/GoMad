<?php
// File: database/migrations/xxxx_xx_xx_xxxxxx_add_avatar_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // avatar_url sudah ada, tidak perlu tambah
        });
    }

    public function down(): void
    {
        //
    }
};