<?php
// File: database/migrations/xxxx_xx_xx_xxxxxx_add_verification_fields_to_payment_agents_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_agents', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('is_verified');
            $table->foreignId('verified_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('payment_agents', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['rejection_reason', 'verified_by', 'verified_at']);
        });
    }
};