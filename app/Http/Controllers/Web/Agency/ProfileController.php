<?php
// File: app/Http/Controllers/Web/Agency/ProfileController.php
// Deskripsi: Web Controller untuk profil agency (FULL)

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
     * Jika ada parameter ?reset=1, tampilkan form setup dari awal
     */
    public function setup(): View|RedirectResponse
    {
        $agency = auth()->user()->agency;
        $isReset = request()->has('reset');
        
        // Jika bukan reset dan profil sudah lengkap, redirect ke dashboard
        if (!$isReset && $agency && $agency->agency_name && $agency->address) {
            return redirect()->route('agency.dashboard')
                ->with('warning', 'Profil agency Anda sudah lengkap.');
        }
        
        return view('agency.profile-setup', compact('agency'));
    }

    public function saveSetup(Request $request): RedirectResponse
    {
        $request->validate([
            'agency_name' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
            'description' => ['required', 'string', 'max:2000'],
            'founded_year' => ['required', 'integer', 'min:1950', 'max:' . date('Y')],
            'contact_person' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'email_alternate' => ['nullable', 'email', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'cover' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'gallery.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'documents' => ['required', 'file', 'mimes:pdf', 'max:10240'], // Max 10MB PDF
        ]);

        $user = auth()->user();
        $agency = $user->agency;
        
        if (!$agency) {
            $slug = $this->agencyProfileService->generateSlug($request->agency_name);
            $agency = $user->agency()->create([
                'agency_name' => $request->agency_name,
                'slug' => $slug,
                'address' => $request->address,
                'description' => $request->description,
                'founded_year' => $request->founded_year,
                'contact_person' => $request->contact_person,
                'contact_alternate' => $request->phone,
                'email_alternate' => $request->email_alternate,
                'is_verified' => false,
            ]);
        } else {
            $agency->update([
                'agency_name' => $request->agency_name,
                'address' => $request->address,
                'description' => $request->description,
                'founded_year' => $request->founded_year,
                'contact_person' => $request->contact_person,
                'contact_alternate' => $request->phone,
                'email_alternate' => $request->email_alternate,
            ]);
        }

        // Update user phone
        $user->update(['phone' => $request->whatsapp]);

        // Upload logo
        if ($request->hasFile('logo')) {
            $this->agencyProfileService->uploadLogo($agency, $request->file('logo'));
        }

        // Upload cover
        if ($request->hasFile('cover')) {
            $this->agencyProfileService->uploadCover($agency, $request->file('cover'));
        }

        // Upload gallery
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $photo) {
                if (count($agency->gallery ?? []) < 10) {
                    $this->agencyProfileService->addGalleryPhoto($agency, $photo);
                }
            }
        }

        // Upload dokumen pengajuan (PDF)
        if ($request->hasFile('documents')) {
            $docPath = $request->file('documents')->store('agencies/' . $agency->id . '/documents', 'public');
            $agency->update(['business_license' => $docPath]);
        }

        // Auto-submit verifikasi
        app(\App\Services\VerificationService::class)->submitVerification($agency);

        return redirect()->route('agency.dashboard')
            ->with('success', 'Data agency berhasil disimpan! Pengajuan verifikasi telah dikirim. Admin akan mereview dalam 1-3 hari kerja.');
    }

    public function edit(): View
    {
        $agency = auth()->user()->agency;
        return view('agency.profile.edit', compact('agency'));
    }

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

    public function uploadLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        try {
            $url = $this->agencyProfileService->uploadLogo(auth()->user()->agency, $request->file('logo'));
            return back()->with('success', 'Logo berhasil diupload!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal upload logo: ' . $e->getMessage());
        }
    }

    public function uploadCover(Request $request): RedirectResponse
    {
        $request->validate([
            'cover' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        try {
            $url = $this->agencyProfileService->uploadCover(auth()->user()->agency, $request->file('cover'));
            return back()->with('success', 'Cover berhasil diupload!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal upload cover: ' . $e->getMessage());
        }
    }

    public function uploadBusinessLicense(Request $request): RedirectResponse
    {
        $request->validate([
            'license' => ['required', 'image', 'mimes:jpeg,png,jpg,pdf', 'max:5120'],
        ]);

        try {
            $url = $this->agencyProfileService->uploadBusinessLicense(auth()->user()->agency, $request->file('license'));
            return back()->with('success', 'Surat izin berhasil diupload!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal upload: ' . $e->getMessage());
        }
    }

    public function addGalleryPhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        try {
            $gallery = $this->agencyProfileService->addGalleryPhoto(auth()->user()->agency, $request->file('photo'));
            return back()->with('success', 'Foto berhasil ditambahkan ke galeri!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal upload: ' . $e->getMessage());
        }
    }

    public function removeGalleryPhoto(int $index): RedirectResponse
    {
        try {
            $gallery = $this->agencyProfileService->removeGalleryPhoto(auth()->user()->agency, $index);
            return back()->with('success', 'Foto berhasil dihapus dari galeri.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal hapus: ' . $e->getMessage());
        }
    }

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

// End of file