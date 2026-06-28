<?php
// File: app/Http/Controllers/Web/Agency/DashboardController.php
// Deskripsi: Web Controller untuk dashboard agency

namespace App\Http\Controllers\Web\Agency;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('agency.dashboard');
    }
}

// End of file