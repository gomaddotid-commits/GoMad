<?php
// File: app/Http/Controllers/Web/Agency/DriverController.php
// Deskripsi: Web Controller untuk manajemen driver agency (Cloudinary)

namespace App\Http\Controllers\Web\Agency;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CloudinaryService;
use App\Services\DriverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;

class DriverController extends Controller
{
    public function __construct(
        private readonly DriverService $driverService,
        private readonly CloudinaryService $cloudinaryService,
    ) {}

    public function index(): View
    {
        $drivers = $this->driverService->getAgencyDrivers(auth()->user()->agency);
        return view('agency.drivers.index', compact('drivers'));
    }

    public function create(): View
    {
        return view('agency.drivers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'name.required' => 'Nama driver harus diisi.',
            'email.required' => 'Email harus diisi.',
            'email.unique' => 'Email sudah terdaftar.',
            'phone.required' => 'Nomor HP harus diisi.',
            'password.required' => 'Password harus diisi.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        try {
            $data = $request->only(['name', 'email', 'phone']);
            $data['password'] = Hash::make($request->password);
            $data['role'] = 'driver';
            $data['agency_id'] = auth()->user()->agency->id;
            $data['is_active'] = true;

            // Upload foto driver via Cloudinary
            if ($request->hasFile('avatar')) {
                $result = app(\App\Services\CloudinaryService::class)->upload($request->file('avatar'), 'drivers');
                $data['avatar_url'] = $result['url'];
            }

            $driver = User::create($data);

            // Kirim WhatsApp welcome
            try {
                app(\App\Services\NotificationService::class)->welcomeDriver($driver);
            } catch (\Exception $e) {
                \Log::error('Welcome WhatsApp to driver failed: ' . $e->getMessage());
            }

            return redirect()->route('agency.drivers.index')
                ->with('success', 'Driver berhasil ditambahkan!');
                
        } catch (\Exception $e) {
            \Log::error('Create driver error: ' . $e->getMessage());
            return back()->with('error', 'Gagal menambahkan driver: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(User $user): View
    {
        if ($user->role !== 'driver' || $user->agency_id !== auth()->user()->agency->id) abort(403);
        return view('agency.drivers.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== 'driver' || $user->agency_id !== auth()->user()->agency->id) abort(403);

        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone,' . $user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data = $request->only(['name', 'email', 'phone', 'is_active']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        // Upload foto baru via Cloudinary
        if ($request->hasFile('avatar')) {
            // Hapus avatar lama dari Cloudinary
            if ($user->avatar_url && str_starts_with($user->avatar_url, 'http')) {
                $publicId = $this->extractCloudinaryPublicId($user->avatar_url);
                if ($publicId) $this->cloudinaryService->delete($publicId);
            }
            
            $result = $this->cloudinaryService->upload($request->file('avatar'), 'drivers');
            $data['avatar_url'] = $result['url'];
        }

        $user->update($data);

        return redirect()->route('agency.drivers.index')
            ->with('success', 'Driver berhasil diupdate!');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->role !== 'driver' || $user->agency_id !== auth()->user()->agency->id) abort(403);

        try {
            // Hapus avatar dari Cloudinary
            if ($user->avatar_url && str_starts_with($user->avatar_url, 'http')) {
                $publicId = $this->extractCloudinaryPublicId($user->avatar_url);
                if ($publicId) $this->cloudinaryService->delete($publicId);
            }
            
            $this->driverService->deleteDriver($user);
            return back()->with('success', 'Driver berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Extract public_id dari Cloudinary URL
     */
    private function extractCloudinaryPublicId(string $url): ?string
    {
        $pattern = '/\/upload\/(?:v\d+\/)?(.+?)\.\w+$/';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
