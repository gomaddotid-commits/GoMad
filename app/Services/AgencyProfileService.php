<?php

namespace App\Services;

use App\Models\Agency;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class AgencyProfileService
{
    public function __construct(
        private readonly CloudinaryService $cloudinaryService,
    ) {}

    public function generateSlug(string $name): string
    {
        return Str::slug($name) . '-' . Str::random(6);
    }

    public function updateProfile(Agency $agency, array $data): Agency
    {
        $agency->update($data);
        return $agency;
    }

    public function uploadLogo(Agency $agency, UploadedFile $file): string
    {
        // Hapus logo lama dari Cloudinary
        if ($agency->logo && str_starts_with($agency->logo, 'http')) {
            $publicId = $this->extractPublicId($agency->logo);
            if ($publicId) $this->cloudinaryService->delete($publicId);
        }

        $result = $this->cloudinaryService->upload($file, 'agencies/logos');
        $agency->update(['logo' => $result['url']]);
        return $result['url'];
    }

    public function uploadCover(Agency $agency, UploadedFile $file): string
    {
        if ($agency->cover_image && str_starts_with($agency->cover_image, 'http')) {
            $publicId = $this->extractPublicId($agency->cover_image);
            if ($publicId) $this->cloudinaryService->delete($publicId);
        }

        $result = $this->cloudinaryService->upload($file, 'agencies/covers');
        $agency->update(['cover_image' => $result['url']]);
        return $result['url'];
    }

    public function uploadBusinessLicense(Agency $agency, UploadedFile $file): string
    {
        if ($agency->business_license && str_starts_with($agency->business_license, 'http')) {
            $publicId = $this->extractPublicId($agency->business_license);
            if ($publicId) $this->cloudinaryService->delete($publicId);
        }

        $result = $this->cloudinaryService->upload($file, 'agencies/licenses');
        $agency->update(['business_license' => $result['url']]);
        return $result['url'];
    }

    public function addGalleryPhoto(Agency $agency, UploadedFile $file): array
    {
        $gallery = $agency->gallery ?? [];
        if (is_string($gallery)) $gallery = json_decode($gallery, true) ?? [];

        if (count($gallery) >= 10) {
            throw new \Exception('Maksimal 10 foto di galeri.');
        }

        $result = $this->cloudinaryService->upload($file, 'agencies/gallery');
        $gallery[] = $result['url'];
        $agency->update(['gallery' => $gallery]);
        return $gallery;
    }

    public function removeGalleryPhoto(Agency $agency, int $index): array
    {
        $gallery = $agency->gallery ?? [];
        if (is_string($gallery)) $gallery = json_decode($gallery, true) ?? [];

        if (isset($gallery[$index])) {
            $url = $gallery[$index];
            if (str_starts_with($url, 'http')) {
                $publicId = $this->extractPublicId($url);
                if ($publicId) $this->cloudinaryService->delete($publicId);
            }
            unset($gallery[$index]);
            $gallery = array_values($gallery);
        }

        $agency->update(['gallery' => $gallery]);
        return $gallery;
    }

    private function extractPublicId(string $url): ?string
    {
        // Extract public_id dari Cloudinary URL
        // https://res.cloudinary.com/CLOUD_NAME/image/upload/v1234567890/folder/filename.jpg
        $pattern = '/\/upload\/(?:v\d+\/)?(.+?)\.\w+$/';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Get public profile data untuk halaman agency
     */
    public function getPublicProfile(Agency $agency): array
    {
        $agency->load([
            'user',
            'vehicles' => function ($query) {
                $query->where('is_active', true);
            },
            'reviews' => function ($query) {
                $query->latest()->limit(5)->with('customer');
            },
        ]);

        $activeSchedules = $agency->schedules()
            ->where('departure_date', '>=', now()->toDateString())
            ->where('is_active', true)
            ->with(['route', 'vehicle'])
            ->limit(5)
            ->get();

        return [
            'agency' => $agency,
            'active_schedules' => $activeSchedules,
            'total_reviews' => $agency->reviews()->count(),
            'average_rating' => $agency->rating,
            'total_vehicles' => $agency->vehicles()->where('is_active', true)->count(),
            'gallery' => $agency->gallery ?? [],
            'services' => $agency->services ?? [],
            'social_media' => $agency->social_media ?? [],
            'business_hours' => $agency->business_hours ?? [],
        ];
    }

    // Di dalam class AgencyProfileService, tambahkan:

    /**
     * Dapatkan daftar provinsi
     */
    public function getProvinces(): Collection
    {
        return \App\Models\Province::orderBy('name')->get();
    }

    /**
     * Dapatkan daftar kota berdasarkan provinsi
     */
    public function getCitiesByProvince(string $provinceCode): Collection
    {
        return \App\Models\City::where('province_code', $provinceCode)
            ->orderBy('name')
            ->get();
    }

    /**
     * Dapatkan daftar kecamatan berdasarkan kota
     */
    public function getDistrictsByCity(string $cityCode): Collection
    {
        return \App\Models\District::where('city_code', $cityCode)
            ->orderBy('name')
            ->get();
    }
}
