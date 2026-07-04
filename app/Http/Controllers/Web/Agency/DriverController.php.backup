<?php
// File: app/Http/Controllers/Web/Agency/DriverController.php
// Deskripsi: Web Controller untuk manajemen driver agency (FULL)

namespace App\Http\Controllers\Web\Agency;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DriverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{
    public function __construct(
        private readonly DriverService $driverService,
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
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $data = $request->only(['name', 'email', 'phone']);
        $data['password'] = Hash::make($request->password);
        $data['role'] = 'driver';
        $data['agency_id'] = auth()->user()->agency->id;
        $data['is_active'] = true;

        // Upload foto driver
        if ($request->hasFile('avatar')) {
            $data['avatar_url'] = $request->file('avatar')->store('drivers', 'public');
        }

        User::create($data);

        return redirect()->route('agency.drivers.index')
            ->with('success', 'Driver berhasil ditambahkan!');
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

        // Upload foto baru
        if ($request->hasFile('avatar')) {
            if ($user->avatar_url && Storage::disk('public')->exists($user->avatar_url)) {
                Storage::disk('public')->delete($user->avatar_url);
            }
            $data['avatar_url'] = $request->file('avatar')->store('drivers', 'public');
        }

        $user->update($data);

        return redirect()->route('agency.drivers.index')
            ->with('success', 'Driver berhasil diupdate!');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->role !== 'driver' || $user->agency_id !== auth()->user()->agency->id) abort(403);

        try {
            $this->driverService->deleteDriver($user);
            return back()->with('success', 'Driver berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

// End of file