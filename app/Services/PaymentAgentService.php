<?php
// File: app/Services/PaymentAgentService.php
// Deskripsi: Service untuk manajemen payment agent (Warung GoMad)

namespace App\Services;

use App\Models\PaymentAgent;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class PaymentAgentService
{
    public function registerAgent(array $data): PaymentAgent
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['owner_name'],
                'email' => $data['email'],
                'phone' => $data['owner_phone'],
                'password' => Hash::make($data['password']),
                'role' => 'payment_agent',
                'is_active' => true,
            ]);

            $agent = PaymentAgent::create([
                'user_id' => $user->id,
                'agent_name' => $data['agent_name'],
                'owner_name' => $data['owner_name'],
                'owner_phone' => $data['owner_phone'],
                'guard_name' => $data['guard_name'] ?? null,
                'guard_phone' => $data['guard_phone'] ?? null,
                'address' => $data['address'],
                'province_code' => $data['province_code'],
                'city_code' => $data['city_code'],
                'district_code' => $data['district_code'] ?? null,                'maps_link' => $data['maps_link'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'pin' => Hash::make($data['pin']),
                'is_active' => true,
                'is_verified' => false,
                'commission_rate' => 2.00,
            ]);

            return $agent;
        });
    }

    public function updateAgent(PaymentAgent $agent, array $data): PaymentAgent
    {
        $updateData = [];
        
        $allowedFields = [
            'agent_name', 'owner_name', 'owner_phone', 'guard_name',
            'guard_phone', 'address', 'kecamatan', 'maps_link',
            'latitude', 'longitude',
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($updateData)) {
            $agent->update($updateData);
        }

        // Update user info
        if (isset($data['owner_name'])) {
            $agent->user->update(['name' => $data['owner_name']]);
        }
        if (isset($data['owner_phone'])) {
            $agent->user->update(['phone' => $data['owner_phone']]);
        }

        return $agent->fresh();
    }

    public function updatePin(PaymentAgent $agent, string $currentPin, string $newPin): void
    {
        if (!Hash::check($currentPin, $agent->pin)) {
            throw new \Exception('PIN saat ini tidak valid.');
        }

        if (strlen($newPin) !== 6 || !is_numeric($newPin)) {
            throw new \Exception('PIN harus 6 digit angka.');
        }

        $agent->update(['pin' => Hash::make($newPin)]);
    }

    public function verifyPin(PaymentAgent $agent, string $pin): bool
    {
        return Hash::check($pin, $agent->pin);
    }

    public function uploadPhoto(PaymentAgent $agent, UploadedFile $file, string $type): string
    {
        $validTypes = ['photo_warung', 'photo_ktp_owner', 'photo_ktp_guard'];
        
        if (!in_array($type, $validTypes)) {
            throw new \Exception('Tipe foto tidak valid.');
        }

        // Delete old file if exists
        $oldPath = $agent->$type;
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $path = $file->store('payment-agents/' . $agent->id . '/' . $type, 'public');
        $agent->update([$type => $path]);

        return Storage::url($path);
    }

    public function getNearbyAgents(float $latitude, float $longitude, float $radiusKm = 10): Collection
    {
        return PaymentAgent::nearby($latitude, $longitude, $radiusKm)->get();
    }

    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadiusKm * $c;
    }

    public function getAgentStats(PaymentAgent $agent): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $thisMonthStart = now()->startOfMonth();
        $thisMonthEnd = now()->endOfMonth();

        $todayTransactions = $agent->cashPayments()
            ->where('status', 'confirmed')
            ->whereBetween('confirmed_at', [$todayStart, $todayEnd])
            ->count();

        $todayAmount = $agent->cashPayments()
            ->where('status', 'confirmed')
            ->whereBetween('confirmed_at', [$todayStart, $todayEnd])
            ->sum('amount');

        $todayCommission = $agent->cashPayments()
            ->where('status', 'confirmed')
            ->whereBetween('confirmed_at', [$todayStart, $todayEnd])
            ->sum('agent_commission');

        $monthTransactions = $agent->cashPayments()
            ->where('status', 'confirmed')
            ->whereBetween('confirmed_at', [$thisMonthStart, $thisMonthEnd])
            ->count();

        $monthAmount = $agent->cashPayments()
            ->where('status', 'confirmed')
            ->whereBetween('confirmed_at', [$thisMonthStart, $thisMonthEnd])
            ->sum('amount');

        $monthCommission = $agent->cashPayments()
            ->where('status', 'confirmed')
            ->whereBetween('confirmed_at', [$thisMonthStart, $thisMonthEnd])
            ->sum('agent_commission');

        $pendingSettlement = $agent->settlements()
            ->where('status', 'pending')
            ->sum('amount_to_settle');

        return [
            'today_transactions' => $todayTransactions,
            'today_amount' => (float) $todayAmount,
            'today_commission' => (float) $todayCommission,
            'month_transactions' => $monthTransactions,
            'month_amount' => (float) $monthAmount,
            'month_commission' => (float) $monthCommission,
            'total_transactions' => $agent->total_transactions,
            'total_commission' => (float) $agent->total_commission,
            'balance_to_settle' => (float) $agent->balance_to_settle,
            'pending_settlement' => (float) $pendingSettlement,
        ];
    }

    public function getActiveAgents(): Collection
    {
        return PaymentAgent::with('user')
            ->where('is_active', true)
            ->where('is_verified', true)
            ->get();
    }

    public function toggleActive(PaymentAgent $agent): void
    {
        $agent->update(['is_active' => !$agent->is_active]);
        
        if (!$agent->is_active) {
            // Notify agent
            app(NotificationService::class)->sendWhatsApp(
                $agent->owner_phone,
                "Warung GoMad *{$agent->agent_name}* dinonaktifkan.\n" .
                "Hubungi admin untuk informasi lebih lanjut."
            );
        }
    }
}

// End of file