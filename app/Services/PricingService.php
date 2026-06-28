<?php
// File: app/Services/PricingService.php
// Deskripsi: Service untuk kalkulasi harga, komisi, dan revenue

namespace App\Services;

use App\Models\RoutePricing;
use App\Models\RouteStop;
use App\Models\Schedule;
use App\Models\PlatformSetting;

class PricingService
{
    public function getRoutePricing(Schedule $schedule, RouteStop $origin, RouteStop $destination): ?RoutePricing
    {
        return RoutePricing::where('schedule_id', $schedule->id)
            ->where('origin_stop_id', $origin->id)
            ->where('destination_stop_id', $destination->id)
            ->first();
    }

    public function calculateCommission(float $totalPrice, string $paymentType): array
    {
        $platformCommissionRate = (float) PlatformSetting::getValue('commission_rate', 5);
        $warungCommissionRate = (float) PlatformSetting::getValue('warung_commission_rate', 2);
        
        if ($paymentType === 'cash') {
            $agentCommission = $totalPrice * ($warungCommissionRate / 100);
            $platformCommission = $totalPrice * ($platformCommissionRate / 100);
            $agencyRevenue = $totalPrice - $agentCommission - $platformCommission;
            
            return [
                'total_price' => $totalPrice,
                'agent_commission' => round($agentCommission, 2),
                'platform_commission' => round($platformCommission, 2),
                'agency_revenue' => round($agencyRevenue, 2),
                'platform_commission_rate' => $platformCommissionRate,
                'warung_commission_rate' => $warungCommissionRate,
            ];
        }
        
        $platformCommission = $totalPrice * ($platformCommissionRate / 100);
        $agencyRevenue = $totalPrice - $platformCommission;
        
        return [
            'total_price' => $totalPrice,
            'platform_commission' => round($platformCommission, 2),
            'agency_revenue' => round($agencyRevenue, 2),
            'agent_commission' => 0,
            'platform_commission_rate' => $platformCommissionRate,
            'warung_commission_rate' => 0,
        ];
    }

    public function validatePriceReasonableness(float $price, float $basePrice): bool
    {
        if ($basePrice <= 0) {
            return true;
        }
        
        $ratio = $price / $basePrice;
        
        return $ratio >= 0.3 && $ratio <= 3.0;
    }

    public function getAllPricingForSchedule(Schedule $schedule): array
    {
        $pricingData = RoutePricing::with(['originStop', 'destinationStop'])
            ->where('schedule_id', $schedule->id)
            ->get();
        
        $result = [];
        
        foreach ($pricingData as $pricing) {
            $originOrder = $pricing->originStop->stop_order;
            $destOrder = $pricing->destinationStop->stop_order;
            
            if (!isset($result[$originOrder])) {
                $result[$originOrder] = [];
            }
            
            $result[$originOrder][$destOrder] = [
                'pricing_id' => $pricing->id,
                'origin_city' => $pricing->originStop->city_name,
                'destination_city' => $pricing->destinationStop->city_name,
                'price' => $pricing->price,
            ];
        }
        
        return $result;
    }

    public function getMinimumPrice(Schedule $schedule): float
    {
        $minPrice = RoutePricing::where('schedule_id', $schedule->id)->min('price');
        return $minPrice ? (float) $minPrice : 0;
    }

    public function getMaximumPrice(Schedule $schedule): float
    {
        $maxPrice = RoutePricing::where('schedule_id', $schedule->id)->max('price');
        return $maxPrice ? (float) $maxPrice : 0;
    }
}

// End of file