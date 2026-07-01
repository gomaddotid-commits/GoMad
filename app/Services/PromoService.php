<?php
// File: app/Services/PromoService.php
// Deskripsi: Service untuk manajemen promo (FIXED - satu kali pakai per customer)

namespace App\Services;

use App\Models\Booking;
use App\Models\Promo;
use App\Models\PromoUsage;
use App\Models\ReferralCode;
use App\Models\ReferralTracking;
use App\Models\User;
use Illuminate\Support\Collection;

class PromoService
{
    /**
     * Generate referral code untuk user baru
     */
    public function generateReferralCode(User $user): ReferralCode
    {
        return ReferralCode::firstOrCreate(
            ['user_id' => $user->id],
            [
                'code' => ReferralCode::generateCode($user->name),
                'total_referred' => 0,
                'successful_referrals' => 0,
            ]
        );
    }

    /**
     * Proses referral saat user baru daftar dengan kode referral
     */
    public function processReferralRegistration(User $newUser, string $referralCode): void
    {
        $referral = ReferralCode::where('code', $referralCode)->first();
        if (!$referral) return;
        
        // Jangan proses jika mereferal diri sendiri
        if ($referral->user_id === $newUser->id) return;

        // 👇 PASTIKAN INI DIJALANKAN
        $newUser->update(['referred_by' => $referral->user_id]);

        ReferralTracking::create([
            'referrer_id' => $referral->user_id,
            'referred_user_id' => $newUser->id,
            'referral_code' => $referralCode,
            'is_successful' => false,
        ]);

        $referral->increment('total_referred');
    }

    /**
     * Cek dan proses referral reward saat user baru transaksi pertama
     */
    public function processReferralReward(Booking $booking): void
    {
        $user = $booking->customer;
        if (!$user->referred_by) return;
        
        // Hanya transaksi pertama yang dihitung
        $paidBookingsCount = $user->customerBookings()
            ->where('status', 'paid')
            ->count();
            
        if ($paidBookingsCount > 1) return;

        $tracking = ReferralTracking::where('referred_user_id', $user->id)
            ->where('is_successful', false)
            ->first();

        if (!$tracking) return;

        // Tentukan besaran diskon berdasarkan total transaksi
        $totalPrice = $booking->total_price;
        $discountPercent = match(true) {
            $totalPrice >= 1000000 => 50,
            $totalPrice >= 500000 => 40,
            $totalPrice >= 250000 => 30,
            $totalPrice >= 100000 => 20,
            default => 10,
        };

        $maxDiscount = match(true) {
            $totalPrice >= 1000000 => 75000,
            $totalPrice >= 500000 => 60000,
            $totalPrice >= 250000 => 50000,
            $totalPrice >= 100000 => 30000,
            default => 15000,
        };

        // Buat promo referral KHUSUS untuk pengajak (referrer)
        $promo = Promo::create([
            'name' => 'Referral Reward dari ' . $user->name,
            'type' => 'referral',
            'description' => "Reward referral dari {$user->name} - Dapatkan diskon untuk booking berikutnya!",
            'discount_percent' => $discountPercent,
            'max_discount' => $maxDiscount,
            'min_purchase' => 0,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'cost_bearer' => 'platform',
            'platform_share_percent' => 100,
            'agency_share_percent' => 0,
            'is_active' => true,
            'created_by' => $tracking->referrer_id, // 👈 Penting: tandai milik siapa promo ini
        ]);

        // Update tracking
        $tracking->update([
            'is_successful' => true,
            'successful_at' => now(),
        ]);

        // Update referral code counter
        $referralCode = ReferralCode::where('user_id', $tracking->referrer_id)->first();
        if ($referralCode) {
            $referralCode->increment('successful_referrals');
        }

        // Notifikasi ke pengajak (referrer)
        $referrer = User::find($tracking->referrer_id);
        if ($referrer && $referrer->phone) {
            app(NotificationService::class)->sendWhatsApp(
                $referrer->phone,
                "🎉 *Selamat!*\n\n" .
                "Teman Anda ({$user->name}) telah berhasil bertransaksi.\n\n" .
                "Anda mendapatkan:\n" .
                "DISKON {$discountPercent}% (maks Rp " . number_format($maxDiscount, 0, ',', '.') . ")\n\n" .
                "Berlaku hingga " . now()->addDays(30)->format('d M Y') . "\n" .
                "Gunakan saat booking berikutnya!"
            );
        }
    }

    /**
     * Cari promo yang tersedia untuk customer
     * HANYA promo yang BELUM PERNAH digunakan oleh customer ini
     */
    /**
     * Cari promo yang tersedia untuk customer dengan filter metode pembayaran
     */
    public function getAvailablePromosForCustomer(User $user, ?int $scheduleId = null, ?string $paymentMethod = null): Collection
    {
        $usedPromoIds = PromoUsage::where('user_id', $user->id)
            ->pluck('promo_id')
            ->toArray();

        $query = Promo::active()
            ->whereNotIn('id', $usedPromoIds);

        // Filter berdasarkan tipe
        $query->where(function ($q) use ($user) {
            // Promo general - tersedia untuk semua
            $q->where('type', 'general');
            
            // Promo referral - HANYA yang dibuat untuk user ini
            $q->orWhere(function ($subQ) use ($user) {
                $subQ->where('type', 'referral')
                    ->where('created_by', $user->id);  // 👈 HANYA referral milik user ini
            });
        });

        // Filter metode pembayaran jika ada
        if ($paymentMethod) {
            $query->where(function ($q) use ($paymentMethod) {
                $q->whereNull('applicable_payment_methods')
                ->orWhere('applicable_payment_methods', '')
                ->orWhere('applicable_payment_methods', 'like', '%' . $paymentMethod . '%');
            });
        }

        $promos = $query->get();

        // Tambahkan promo selektif dari schedule
        if ($scheduleId) {
            $schedulePromos = Promo::whereHas('schedules', function($q) use ($scheduleId) {
                $q->where('schedule_id', $scheduleId);
            })
            ->active()
            ->whereNotIn('id', $usedPromoIds)
            ->when($paymentMethod, function ($q) use ($paymentMethod) {
                $q->where(function ($sq) use ($paymentMethod) {
                    $sq->whereNull('applicable_payment_methods')
                    ->orWhere('applicable_payment_methods', '')
                    ->orWhere('applicable_payment_methods', 'like', '%' . $paymentMethod . '%');
                });
            })
            ->get();
            
            $promos = $promos->merge($schedulePromos);
        }

        return $promos;
    }

    /**
     * Cari promo yang tersedia untuk schedule tertentu (selektif)
     */
    public function getAvailablePromosForSchedule(int $scheduleId): Collection
    {
        $schedule = \App\Models\Schedule::with('route')->find($scheduleId);
        if (!$schedule) return collect();

        return Promo::active()
            ->selective()
            ->where(function ($q) use ($schedule) {
                $q->whereNull('route_id')
                  ->orWhere('route_id', $schedule->route_id);
            })
            ->where(function ($q) use ($schedule) {
                $q->whereNull('travel_class')
                  ->orWhere('travel_class', $schedule->travel_class);
            })
            ->get();
    }

    /**
     * Hitung diskon dari promo
     */
    public function calculateDiscount(Promo $promo, float $totalPrice): float
    {
        if ($totalPrice < $promo->min_purchase) return 0;

        $discount = $totalPrice * ($promo->discount_percent / 100);
        return min($discount, (float) $promo->max_discount);
    }

    /**
     * Cek apakah customer masih bisa menggunakan promo ini
     */
    public function canUsePromo(User $user, Promo $promo): bool
    {
        // Cek apakah promo masih aktif
        if (!$promo->isActiveNow()) return false;

        // Cek apakah customer sudah pernah menggunakan promo ini
        $alreadyUsed = PromoUsage::where('user_id', $user->id)
            ->where('promo_id', $promo->id)
            ->exists();

        if ($alreadyUsed) return false;
        
        // 👇 TAMBAHKAN: Promo referral hanya untuk pemiliknya
        if ($promo->type === 'referral' && $promo->created_by !== $user->id) {
            return false;
        }

        return true;
    }

    /**
     * Gunakan promo untuk booking
     */
    public function applyPromo(Booking $booking, Promo $promo): ?PromoUsage
    {
        // Validasi
        if (!$this->canUsePromo($booking->customer, $promo)) {
            return null;
        }

        $discount = $this->calculateDiscount($promo, (float) $booking->total_price);
        
        if ($discount <= 0) return null;

        return PromoUsage::create([
            'promo_id' => $promo->id,
            'user_id' => $booking->customer_id,
            'booking_id' => $booking->id,
            'discount_amount' => $discount,
        ]);
    }

    /**
     * Aktifkan promo selektif untuk schedule
     */
    public function attachPromoToSchedule(int $promoId, int $scheduleId): void
    {
        \App\Models\Schedule::findOrFail($scheduleId)
            ->promos()
            ->syncWithoutDetaching([$promoId]);
    }

    /**
     * Dapatkan riwayat penggunaan promo oleh customer
     */
    public function getCustomerPromoHistory(User $user): Collection
    {
        return PromoUsage::where('user_id', $user->id)
            ->with(['promo', 'booking'])
            ->latest()
            ->get();
    }
}

// End of file