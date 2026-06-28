<?php
// File: app/Http/Controllers/Web/Admin/ReportController.php
// Deskripsi: Web Controller untuk laporan admin

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('admin.reports');
    }
}

// End of file