<?php
// File: app/Http/Controllers/Web/Public/ListingController.php
// Deskripsi: Web Controller untuk listing agency

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListingController extends Controller
{
    public function index(Request $request): View
    {
        $query = Agency::where('is_verified', true);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('agency_name', 'like', '%' . $request->search . '%')
                    ->orWhere('address', 'like', '%' . $request->search . '%');
            });
        }

        $agencies = $query->orderByDesc('rating')->paginate(12);

        return view('public-pages.listing', compact('agencies'));
    }
}

// End of file