<?php
// File: app/Http/Controllers/Web/Agency/ReportController.php
// Deskripsi: Web Controller untuk laporan agency

namespace App\Http\Controllers\Web\Agency;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('agency.reports');
    }

    public function reviews(): View
    {
        $reviews = auth()->user()->agency->reviews()->with('customer')->latest()->paginate(10);
        return view('agency.reviews', compact('reviews'));
    }
}

// End of file