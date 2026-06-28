<?php
// File: database/migrations/xxxx_xx_xx_add_deposit_fields_to_agency_wallets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agency_wallets', function (Blueprint $table) {
            $table->decimal('deposit_balance', 12, 2)->default(0)->after('pending_balance')->comment('Saldo deposit (top up)');
            $table->decimal('cod_hold_balance', 12, 2)->default(0)->after('deposit_balance')->comment('Saldo COD yang ditahan');
        });
    }

    public function down(): void
    {
        Schema::table('agency_wallets', function (Blueprint $table) {
            $table->dropColumn(['deposit_balance', 'cod_hold_balance']);
        });
    }
};