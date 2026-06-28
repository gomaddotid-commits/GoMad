<?php
// File: database/migrations/0000_00_00_000000_disable_foreign_key_checks.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};