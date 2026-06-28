<?php
// File: app/Http/Controllers/Web/Admin/DriverController.php
// Deskripsi: Web Controller untuk monitoring driver oleh admin

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::where('role', 'driver')->with('driverAgency');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        $drivers = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.drivers.index', compact('drivers'));
    }
}

// End of file