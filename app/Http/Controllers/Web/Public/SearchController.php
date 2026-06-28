<?php
// File: app/Http/Controllers/Web/Public/SearchController.php
// Deskripsi: Web Controller untuk pencarian jadwal public (FIXED - non-strict)

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function search(Request $request): View
    {
        // Tidak perlu validasi strict, biarkan filter opsional
        // Semua logika filter sudah di handle di view
        
        return view('public-pages.search');
    }
}

// End of file