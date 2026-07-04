<?php
// File: app/Services/AgencyProfileService.php
// Deskripsi: Service untuk manajemen profil agency

namespace App\Services;

use App\Models\Agency;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AgencyProfileService
{
    public function updateProfile(Agency $agency, array $data): Agency
    {
        $updateData = [];
        
        $allowedFields = [
            'agency_name', 'address', 'description', 'founded_year',
            'contact_person', 'contact_alternate', 'email_alternate',
            'services', 'social_media', 'business_hours', 'zone_coverage',
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (isset($data['agency_name']) && $data['agency_name'] !== $agency->agency_name) {
            $updateData['slug'] = $this->generateSlug($data['agency_name'], $agency->id);
        }
        
        if (!empty($updateData)) {
            $agency->update($updateData);
        }
        
        return $agency->fresh();
    }

    public function uploadLogo(Agency $agency, UploadedFile $file): string
    {
        $this->deleteOldFile($agency->logo);
        
        $path = $file->store('agencies/' . $agency->id . '/logo', 'public');
        $agency->update(['logo' => $path]);
        
        return Storage::url($path);
    }

    public function uploadCover(Agency $agency, UploadedFile $file): string
    {
        $this->deleteOldFile($agency->cover_image);
        
        $path = $file->store('agencies/' . $agency->id . '/cover', 'public');
        $agency->update(['cover_image' => $path]);
        
        return Storage::url($path);
    }

    public function uploadBusinessLicense(Agency $agency, UploadedFile $file): string
    {
        $this->deleteOldFile($agency->business_license);
        
        $path = $file->store('agencies/' . $agency->id . '/license', 'public');
        $agency->update(['business_license' => $path]);
        
        return Storage::url($path);
    }

    public function addGalleryPhoto(Agency $agency, UploadedFile $file): array
    {
        $gallery = $agency->gallery ?? [];
        
        if (count($gallery) >= 10) {
            throw new \Exception('Maksimal 10 foto dalam galeri.');
        }
        
        $path = $file->store('agencies/' . $agency->id . '/gallery', 'public');
        $gallery[] = $path;
        
        $agency->update(['gallery' => $gallery]);
        
        return $gallery;
    }

    public function removeGalleryPhoto(Agency $agency, int $index): array
    {
        $gallery = $agency->gallery ?? [];
        
        if (!isset($gallery[$index])) {
            throw new \Exception('Foto galeri tidak ditemukan.');
        }
        
        $this->deleteOldFile($gallery[$index]);
        unset($gallery[$index]);
        
        $gallery = array_values($gallery);
        $agency->update(['gallery' => $gallery]);
        
        return $gallery;
    }

    public function generateSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;
        
        while (true) {
            $query = Agency::where('slug', $slug);
            
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            
            if (!$query->exists()) {
                break;
            }
            
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    public function getProfileCompletionPercentage(Agency $agency): int
    {
        return $agency->profile_complete_percentage;
    }

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

    private function deleteOldFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}

// End of file