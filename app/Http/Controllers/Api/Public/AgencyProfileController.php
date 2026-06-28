<?php
// File: app/Http/Controllers/Api/Public/AgencyProfileController.php
// Deskripsi: API Controller untuk profil agency public

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AgencyResource;
use App\Http\Resources\Api\ReviewResource;
use App\Http\Resources\Api\ScheduleResource;
use App\Models\Agency;
use App\Services\AgencyProfileService;
use Illuminate\Http\JsonResponse;

class AgencyProfileController extends Controller
{
    public function __construct(
        private readonly AgencyProfileService $agencyProfileService,
    ) {}

    public function show(string $slug): JsonResponse
    {
        $agency = Agency::where('slug', $slug)
            ->where('is_verified', true)
            ->firstOrFail();

        $profileData = $this->agencyProfileService->getPublicProfile($agency);

        return response()->json([
            'success' => true,
            'message' => 'Profil agency berhasil diambil.',
            'data' => $profileData,
            'meta' => null,
        ]);
    }

    public function reviews(string $slug): JsonResponse
    {
        $agency = Agency::where('slug', $slug)->firstOrFail();

        $reviews = $agency->reviews()
            ->with('customer')
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Review agency berhasil diambil.',
            'data' => ReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
                'average_rating' => (float) $agency->rating,
            ],
        ]);
    }

    public function schedules(string $slug): JsonResponse
    {
        $agency = Agency::where('slug', $slug)->firstOrFail();

        $schedules = $agency->schedules()
            ->with(['route', 'vehicle'])
            ->where('departure_date', '>=', now()->toDateString())
            ->where('is_active', true)
            ->orderBy('departure_date')
            ->orderBy('departure_time')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal agency berhasil diambil.',
            'data' => ScheduleResource::collection($schedules),
            'meta' => [
                'current_page' => $schedules->currentPage(),
                'last_page' => $schedules->lastPage(),
                'total' => $schedules->total(),
            ],
        ]);
    }
}

// End of file