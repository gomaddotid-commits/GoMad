<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Booking;
use App\Models\PaymentAgent;
use App\Models\Review;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\VehicleRentalSetting;
use Illuminate\Http\JsonResponse;

class LandingController extends Controller
{
    /**
     * Semua data landing page dalam satu response
     */
    public function all(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $this->getStats(),
                'popular_routes' => $this->getPopularRoutes(),
                'top_agencies' => $this->getTopAgencies(),
                'testimonials' => $this->getTestimonials(),
                'rental_cars' => $this->getRentalCars(),
                'today_schedules' => $this->getTodaySchedules(),
                'warungs' => $this->getWarungs(),
            ],
        ]);
    }

    /**
     * Statistik platform
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->getStats(),
        ]);
    }

    /**
     * Rute populer
     */
    public function popularRoutes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->getPopularRoutes(),
        ]);
    }

    /**
     * Agency terbaik
     */
    public function topAgencies(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->getTopAgencies(),
        ]);
    }

    /**
     * Testimoni
     */
    public function testimonials(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->getTestimonials(),
        ]);
    }

    /**
     * Mobil rental
     */
    public function rentalCars(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->getRentalCars(),
        ]);
    }

    /**
     * Jadwal hari ini
     */
    public function todaySchedules(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->getTodaySchedules(),
        ]);
    }

    /**
     * Warung GoMad
     */
    public function warungs(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->getWarungs(),
        ]);
    }

    // ═══════════════════════════════════
    // PRIVATE METHODS
    // ═══════════════════════════════════

    private function getStats(): array
    {
        return [
            'total_agencies' => Agency::where('is_verified', true)->count(),
            'total_routes' => Route::where('is_active', true)->count(),
            'total_rental_cars' => VehicleRentalSetting::where('is_available_for_rental', true)->count(),
            'total_warungs' => PaymentAgent::where('is_verified', true)->count(),
            'total_bookings' => Booking::count(),
            'total_customers' => \App\Models\User::where('role', 'customer')->count(),
        ];
    }

    private function getPopularRoutes(): array
    {
        return Route::where('is_active', true)
            ->withCount(['schedules' => fn($q) => $q->where('departure_date', '>=', now()->toDateString())->where('is_active', true)])
            ->orderByDesc('schedules_count')
            ->limit(6)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'route_name' => $r->route_name,
                'origin_city' => $r->origin_city,
                'destination_city' => $r->destination_city,
                'schedules_count' => $r->schedules_count,
                'photo_url' => $r->photo_url,
                'min_price' => (float) ($r->schedules()->min('price_per_seat') ?? 0),
            ])
            ->toArray();
    }

    private function getTopAgencies(): array
    {
        return Agency::where('is_verified', true)
            ->orderByDesc('rating')
            ->orderByDesc('total_bookings')
            ->limit(4)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'agency_name' => $a->agency_name,
                'slug' => $a->slug,
                'logo' => $a->logo,
                'rating' => (float) $a->rating,
                'total_bookings' => $a->total_bookings,
                'address' => $a->address,
            ])
            ->toArray();
    }

    private function getTestimonials(): array
    {
        return Review::with('customer', 'agency')
            ->whereNotNull('review')
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'review' => $r->review,
                'customer_name' => $r->customer->name ?? 'Anonim',
                'customer_avatar' => $r->customer->avatar_url ?? null,
                'agency_name' => $r->agency->agency_name ?? '-',
                'created_at' => $r->created_at->format('d M Y'),
            ])
            ->toArray();
    }

    private function getRentalCars(): array
    {
        return VehicleRentalSetting::with(['vehicle.agency'])
            ->where('is_available_for_rental', true)
            ->whereHas('vehicle', fn($q) => $q->where('is_active', true))
            ->whereHas('vehicle.agency', fn($q) => $q->where('is_verified', true))
            ->latest()
            ->limit(4)
            ->get()
            ->map(function ($car) {
                $v = $car->vehicle;
                return [
                    'id' => $car->id,
                    'vehicle_id' => $v->id,
                    'brand' => $v->brand,
                    'model' => $v->model,
                    'year' => $v->year,
                    'plate_number' => $v->plate_number,
                    'vehicle_image' => $v->vehicle_image,
                    'price_per_day' => (float) ($car->price_per_day ?? 0),
                    'price_per_hour' => (float) ($car->price_per_hour ?? 0),
                    'allow_self_drive' => $car->allow_self_drive,
                    'allow_with_driver' => $car->allow_with_driver,
                    'agency_name' => $v->agency->agency_name ?? '-',
                    'specifications' => $car->specifications,
                ];
            })
            ->toArray();
    }

    private function getTodaySchedules(): array
    {
        return Schedule::with(['route', 'agency', 'vehicle'])
            ->where('departure_date', now()->toDateString())
            ->where('is_active', true)
            ->orderBy('departure_time')
            ->limit(8)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'route_name' => $s->route->route_name ?? '-',
                'origin_city' => $s->route->origin_city ?? '-',
                'destination_city' => $s->route->destination_city ?? '-',
                'departure_time' => $s->departure_time,
                'price_per_seat' => (float) $s->price_per_seat,
                'travel_class' => $s->travel_class,
                'available_seats' => $s->available_seats,
                'agency_name' => $s->agency->agency_name ?? '-',
                'vehicle_plate' => $s->vehicle->plate_number ?? '-',
            ])
            ->toArray();
    }

    private function getWarungs(): array
    {
        return PaymentAgent::where('is_verified', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(fn($w) => [
                'agent_name' => $w->agent_name,
                'address' => $w->address,
                'latitude' => (float) $w->latitude,
                'longitude' => (float) $w->longitude,
                'owner_phone' => $w->owner_phone,
                'maps_link' => $w->maps_link,
                'kecamatan' => $w->kecamatan,
            ])
            ->toArray();
    }
}