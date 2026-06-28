<?php
// File: database/migrations/xxxx_xx_xx_create_promos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['referral', 'general', 'selective']);
            $table->text('description')->nullable();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('max_discount', 10, 2)->default(0);
            $table->decimal('min_purchase', 10, 2)->default(0);
            $table->foreignId('route_id')->nullable()->constrained('routes')->nullOnDelete();
            $table->string('travel_class')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('cost_bearer', ['platform', 'agency', 'shared'])->default('platform');
            $table->decimal('platform_share_percent', 5, 2)->default(100);
            $table->decimal('agency_share_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('type');
            $table->index('is_active');
            $table->index(['start_date', 'end_date']);
        });

        // Tabel pivot: promo yang diaktifkan per schedule (untuk promo selektif)
        Schema::create('promo_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_id')->constrained('promos')->cascadeOnDelete();
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['promo_id', 'schedule_id'], 'promo_schedule_unique');
        });

        // Tabel untuk referral codes
        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->integer('total_referred')->default(0);
            $table->integer('successful_referrals')->default(0);
            $table->timestamps();
        });

        // Tabel untuk tracking referral
        Schema::create('referral_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('referral_code');
            $table->boolean('is_successful')->default(false);
            $table->timestamp('successful_at')->nullable();
            $table->timestamps();
        });

        // Tambah field referred_by di users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('referred_by')->nullable()->after('role')->constrained('users')->nullOnDelete();
        });

        // Tabel untuk menyimpan klaim promo oleh customer
        Schema::create('promo_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_id')->constrained('promos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('promo_code')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn('referred_by');
        });
        Schema::dropIfExists('promo_usages');
        Schema::dropIfExists('referral_trackings');
        Schema::dropIfExists('referral_codes');
        Schema::dropIfExists('promo_schedule');
        Schema::dropIfExists('promos');
    }
};