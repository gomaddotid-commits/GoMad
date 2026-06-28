<?php
// File: app/Http/Controllers/Web/Admin/CustomerController.php
// Deskripsi: Web Controller untuk manajemen customer oleh admin

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::where('role', 'customer');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $user): View
    {
        if ($user->role !== 'customer') {
            abort(404);
        }

        $bookings = $user->customerBookings()
            ->with(['schedule.route', 'originStop', 'destinationStop'])
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.customers.show', compact('user', 'bookings'));
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $user->update(['is_active' => !$user->is_active]);
        return back()->with('success', 'Status customer diubah.');
    }

    public function ban(Request $request, User $user): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $user->update([
            'banned_at' => now(),
            'banned_reason' => $request->reason,
            'is_active' => false,
        ]);
        return back()->with('success', 'Customer dibanned.');
    }

    public function unban(User $user): RedirectResponse
    {
        $user->update([
            'banned_at' => null,
            'banned_reason' => null,
            'is_active' => true,
        ]);
        return back()->with('success', 'Customer di-unbanned.');
    }
}

// End of file