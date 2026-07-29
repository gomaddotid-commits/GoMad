<?php

namespace App\Http\Controllers\Web\Agency;

use App\Http\Controllers\Controller;
use App\Services\AgencyProfileService;
use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly AgencyProfileService $agencyProfileService,
    ) {}

    /**
     * Halaman setup profil agency
     */
    public function setup(): View|RedirectResponse
    {
        $agency = auth()->user()->agency;
        $isReset = request()->has('reset');
        
        if (!$isReset && $agency && $agency->agency_name && $agency->address) {
            return redirect()->route('agency.dashboard')
                ->with('warning', 'Profil agency Anda sudah lengkap.');
        }
        
        return view('agency.profile-setup', compact('agency'));
    }

    /**
     * Simpan setup profil agency
     */
    public function saveSetup(Request $request): RedirectResponse
    {
        $request->validate([
            'agency_name' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
            'description' => ['required', 'string', 'max:2000'],
            'founded_year' => ['required', 'integer', 'min:1950', 'max:' . date('Y')],
            'contact_person' => ['required', 'string', 'max:100'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email_alternate' => ['nullable', 'email', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'cover' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'gallery.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'documents' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'logo.max' => 'Logo maksimal 2MB. Silakan kompres gambar Anda.',
            'cover.max' => 'Cover maksimal 5MB. Silakan kompres gambar Anda.',
            'gallery.*.max' => 'Setiap foto galeri maksimal 2MB.',
            'documents.max' => 'Dokumen PDF maksimal 10MB.',
            'documents.required' => 'Dokumen verifikasi wajib diupload.',
            'documents.mimes' => 'Dokumen harus dalam format PDF.',
        ]);

        $user = auth()->user();
        $agency = $user->agency;
        
        // ⚡ Tentukan phone: kalau checkbox dicentang atau phone kosong, pakai whatsapp
        $phone = $request->phone;
        if (empty($phone)) {
            $phone = $request->whatsapp;
        }
        
        // Update user phone (whatsapp)
        $user->update(['phone' => $request->whatsapp]);
        
        if (!$agency) {
            $slug = $this->agencyProfileService->generateSlug($request->agency_name);
            $agency = $user->agency()->create([
                'agency_name' => $request->agency_name,
                'slug' => $slug,
                'address' => $request->address,
                'description' => $request->description,
                'founded_year' => $request->founded_year,
                'contact_person' => $request->contact_person,
                'contact_alternate' => $phone,
                'email_alternate' => $request->email_alternate,
                'province_code' => $request->province_code,
                'city_code' => $request->city_code,
                'district_code' => $request->district_code,
                'coverage_cities' => $request->coverage_cities ?? [],
                'is_verified' => false,
            ]);
        } else {
            $agency->update([
                'agency_name' => $request->agency_name,
                'address' => $request->address,
                'description' => $request->description,
                'founded_year' => $request->founded_year,
                'contact_person' => $request->contact_person,
                'contact_alternate' => $phone,
                'email_alternate' => $request->email_alternate,
                'province_code' => $request->province_code,
                'city_code' => $request->city_code,
                'district_code' => $request->district_code,
                'coverage_cities' => $request->coverage_cities ?? [],
            ]);
        }

        // Upload logo
        if ($request->hasFile('logo')) {
            try {
                $this->agencyProfileService->uploadLogo($agency, $request->file('logo'));
            } catch (\Exception $e) {
                return back()->with('error', 'Logo: ' . $e->getMessage())->withInput();
            }
        }

        // Upload cover
        if ($request->hasFile('cover')) {
            try {
                $this->agencyProfileService->uploadCover($agency, $request->file('cover'));
            } catch (\Exception $e) {
                return back()->with('error', 'Cover: ' . $e->getMessage())->withInput();
            }
        }

        // Upload gallery
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $photo) {
                try {
                    if (count($agency->gallery ?? []) < 10) {
                        $this->agencyProfileService->addGalleryPhoto($agency, $photo);
                    }
                } catch (\Exception $e) {
                    return back()->with('error', 'Gallery: ' . $e->getMessage())->withInput();
                }
            }
        }

        // Upload dokumen verifikasi PDF via Cloudinary
        if ($request->hasFile('documents')) {
            try {
                $this->agencyProfileService->uploadBusinessDocument($agency, $request->file('documents'));
            } catch (\Exception $e) {
                return back()->with('error', 'Dokumen: ' . $e->getMessage())->withInput();
            }
        }

        // Auto-submit verifikasi
        app(\App\Services\VerificationService::class)->submitVerification($agency);

        return redirect()->route('agency.dashboard')
            ->with('success', 'Data agency berhasil disimpan! Pengajuan verifikasi telah dikirim. Admin akan mereview dalam 1-3 hari kerja.');
    }

    /**
     * Halaman edit profil
     */
    public function edit(): View
    {
        $agency = auth()->user()->agency;
        return view('agency.profile.edit', compact('agency'));
    }

    /**
     * Update profil
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'agency_name' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'founded_year' => ['nullable', 'integer', 'min:1950', 'max:' . date('Y')],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'contact_alternate' => ['nullable', 'string', 'max:20'],
            'email_alternate' => ['nullable', 'email', 'max:100'],
            'services' => ['nullable', 'array'],
            'social_media' => ['nullable', 'array'],
            'business_hours' => ['nullable', 'array'],
            'zone_coverage' => ['nullable', 'array'],
        ]);

        try {
            $this->agencyProfileService->updateProfile(auth()->user()->agency, $request->all());
            return back()->with('success', 'Profil berhasil diupdate!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal update: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Upload logo (max 2MB)
     */
    public function uploadLogo(Request $request): RedirectResponse
    {
        // ✅ ENHANCED validation
        $request->validate([
            'logo' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:2048',  // 2MB
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',  // ✅ Tambahkan
            ],
        ], [
            'logo.max' => 'Logo maksimal 2MB.',
            'logo.dimensions' => 'Logo harus berukuran antara 100x100px sampai 2000x2000px.',
        ]);

        try {
            $this->agencyProfileService->uploadLogo(auth()->user()->agency, $request->file('logo'));
            return back()->with('success', 'Logo berhasil diupload!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal upload logo: ' . $e->getMessage());
        }
    }

    /**
     * Upload cover (max 5MB)
     */
    public function uploadCover(Request $request): RedirectResponse
    {
        $request->validate([
            'cover' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            'cover.max' => 'Cover maksimal 5MB.',
        ]);

        try {
            $this->agencyProfileService->uploadCover(auth()->user()->agency, $request->file('cover'));
            return back()->with('success', 'Cover berhasil diupload!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal upload cover: ' . $e->getMessage());
        }
    }

    /**
     * Upload dokumen license (max 10MB)
     */
    public function uploadBusinessLicense(Request $request): RedirectResponse
    {
        $request->validate([
            'license' => ['required', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:10240'],
        ], [
            'license.max' => 'Dokumen maksimal 10MB.',
        ]);

        try {
            $this->agencyProfileService->uploadBusinessLicense(auth()->user()->agency, $request->file('license'));
            return back()->with('success', 'Dokumen berhasil diupload!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal upload: ' . $e->getMessage());
        }
    }

    /**
     * Tambah foto gallery (max 2MB)
     */
    public function addGalleryPhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'photo.max' => 'Foto maksimal 2MB.',
        ]);

        try {
            $this->agencyProfileService->addGalleryPhoto(auth()->user()->agency, $request->file('photo'));
            return back()->with('success', 'Foto berhasil ditambahkan ke galeri!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal upload: ' . $e->getMessage());
        }
    }

    /**
     * Hapus foto gallery
     */
    public function removeGalleryPhoto(int $index): RedirectResponse
    {
        try {
            $this->agencyProfileService->removeGalleryPhoto(auth()->user()->agency, $index);
            return back()->with('success', 'Foto berhasil dihapus dari galeri.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal hapus: ' . $e->getMessage());
        }
    }

    /**
     * Ajukan verifikasi
     */
    public function submitVerification(): RedirectResponse
    {
        try {
            app(VerificationService::class)->submitVerification(auth()->user()->agency);
            return back()->with('success', 'Pengajuan verifikasi berhasil dikirim! Admin akan mereview dalam 1-3 hari kerja.');
        } catch (\Exception $e) {
            return back()->with('warning', $e->getMessage());
        }
    }
}