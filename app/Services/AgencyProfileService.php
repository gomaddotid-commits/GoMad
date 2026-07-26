<?php

namespace App\Services;

use App\Models\Agency;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

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

    /**
     * Upload logo agency (max 2MB)
     */
    public function uploadLogo(Agency $agency, UploadedFile $file): string
    {
        // Hapus logo lama dari Cloudinary
        if ($agency->logo && str_starts_with($agency->logo, 'http')) {
            $publicId = $this->extractPublicId($agency->logo);
            if ($publicId) $this->cloudinaryService->delete($publicId);
        }

        // Max 2MB untuk logo
        $result = $this->cloudinaryService->uploadWithLimit($file, 'agencies/logos', 2048);
        $agency->update(['logo' => $result['url']]);
        return $result['url'];
    }

    /**
     * Upload cover image agency (max 5MB)
     */
    public function uploadCover(Agency $agency, UploadedFile $file): string
    {
        if ($agency->cover_image && str_starts_with($agency->cover_image, 'http')) {
            $publicId = $this->extractPublicId($agency->cover_image);
            if ($publicId) $this->cloudinaryService->delete($publicId);
        }

        // Max 5MB untuk cover
        $result = $this->cloudinaryService->uploadWithLimit($file, 'agencies/covers', 5120);
        $agency->update(['cover_image' => $result['url']]);
        return $result['url'];
    }

    /**
     * Upload surat izin usaha (max 10MB)
     */
    public function uploadBusinessLicense(Agency $agency, UploadedFile $file): string
    {
        if ($agency->business_license && str_starts_with($agency->business_license, 'http')) {
            $publicId = $this->extractPublicId($agency->business_license);
            if ($publicId) $this->cloudinaryService->delete($publicId);
        }

        // Max 10MB untuk dokumen
        $result = $this->cloudinaryService->uploadWithLimit($file, 'agencies/licenses', 10240);
        $agency->update(['business_license' => $result['url']]);
        return $result['url'];
    }

    /**
     * Upload dokumen verifikasi PDF ke Cloudinary (max 10MB)
     */
    public function uploadBusinessDocument(Agency $agency, UploadedFile $file): string
    {
        // Hapus dokumen lama dari Cloudinary jika ada
        if ($agency->business_license && str_starts_with($agency->business_license, 'http')) {
            $publicId = $this->extractPublicId($agency->business_license);
            if ($publicId) {
                $this->cloudinaryService->delete($publicId);
            }
        }

        // Max 10MB untuk dokumen PDF
        $result = $this->cloudinaryService->uploadWithLimit($file, 'agencies/documents', 10240);
        $agency->update(['business_license' => $result['url']]);
        return $result['url'];
    }

    /**
     * Tambah foto gallery (max 2MB per foto, max 10 foto)
     */
    public function addGalleryPhoto(Agency $agency, UploadedFile $file): array
    {
        $gallery = $agency->gallery ?? [];
        if (is_string($gallery)) $gallery = json_decode($gallery, true) ?? [];

        if (count($gallery) >= 10) {
            throw new \Exception('Maksimal 10 foto di galeri.');
        }

        // Max 2MB per foto gallery
        $result = $this->cloudinaryService->uploadWithLimit($file, 'agencies/gallery', 2048);
        $gallery[] = $result['url'];
        $agency->update(['gallery' => $gallery]);
        return $gallery;
    }

    /**
     * Hapus foto dari gallery
     */
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

    /**
     * Extract public_id dari Cloudinary URL
     */
    private function extractPublicId(string $url): ?string
    {
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