<?php
// File: app/Http/Controllers/Api/Agency/ProfileController.php
// Deskripsi: API Controller untuk profil agency

namespace App\Http\Controllers\Api\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateAgencyRequest;
use App\Http\Resources\Api\AgencyDetailResource;
use App\Services\AgencyProfileService;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private readonly AgencyProfileService $agencyProfileService,
        private readonly VerificationService $verificationService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;
        $agency->load(['wallet', 'vehicles', 'drivers']);

        return response()->json([
            'success' => true,
            'message' => 'Profil agency berhasil diambil.',
            'data' => new AgencyDetailResource($agency),
            'meta' => null,
        ]);
    }

    public function update(UpdateAgencyRequest $request): JsonResponse
    {
        $agency = $request->user()->agency;

        $agency = $this->agencyProfileService->updateProfile($agency, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Profil agency berhasil diupdate.',
            'data' => new AgencyDetailResource($agency),
            'meta' => null,
        ]);
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $agency = $request->user()->agency;
        $url = $this->agencyProfileService->uploadLogo($agency, $request->file('logo'));

        return response()->json([
            'success' => true,
            'message' => 'Logo berhasil diupload.',
            'data' => ['logo_url' => $url],
            'meta' => null,
        ]);
    }

    public function uploadCover(Request $request): JsonResponse
    {
        $request->validate([
            'cover' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:5120'],
        ]);

        $agency = $request->user()->agency;
        $url = $this->agencyProfileService->uploadCover($agency, $request->file('cover'));

        return response()->json([
            'success' => true,
            'message' => 'Cover berhasil diupload.',
            'data' => ['cover_url' => $url],
            'meta' => null,
        ]);
    }

    public function addGalleryPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $agency = $request->user()->agency;

        try {
            $gallery = $this->agencyProfileService->addGalleryPhoto($agency, $request->file('photo'));

            return response()->json([
                'success' => true,
                'message' => 'Foto galeri berhasil ditambahkan.',
                'data' => [
                    'gallery' => collect($gallery)->map(fn($item) => $item)->toArray(),
                ],
                'meta' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'meta' => null,
            ], 422);
        }
    }

    public function removeGalleryPhoto(Request $request, int $index): JsonResponse
    {
        $agency = $request->user()->agency;

        try {
            $gallery = $this->agencyProfileService->removeGalleryPhoto($agency, $index);

            return response()->json([
                'success' => true,
                'message' => 'Foto galeri berhasil dihapus.',
                'data' => [
                    'gallery' => collect($gallery)->map(fn($item) => $item)->toArray(),
                ],
                'meta' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'meta' => null,
            ], 422);
        }
    }

    public function submitVerification(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;

        try {
            $verification = $this->verificationService->submitVerification($agency);

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan verifikasi berhasil dikirim.',
                'data' => [
                    'id' => $verification->id,
                    'status' => $verification->status,
                    'created_at' => $verification->created_at->format('Y-m-d H:i:s'),
                ],
                'meta' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'meta' => null,
            ], 422);
        }
    }
}

// End of file