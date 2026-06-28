<?php
// File: app/Http/Controllers/Web/Admin/DashboardController.php
// Deskripsi: Web Controller untuk dashboard admin

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard');
    }
}

// End of file