<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Payments
        Schema::table('payments', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Cash Payments
        Schema::table('cash_payments', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Withdrawals
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Settlements
        Schema::table('settlements', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('cash_payments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('settlements', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};