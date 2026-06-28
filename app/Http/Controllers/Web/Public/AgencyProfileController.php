<?php
// File: app/Http/Controllers/Web/Public/AgencyProfileController.php
// Deskripsi: Web Controller untuk profil agency public

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Services\AgencyProfileService;
use Illuminate\View\View;

class AgencyProfileController extends Controller
{
    public function __construct(
        private readonly AgencyProfileService $agencyProfileService,
    ) {}

    public function show(string $slug): View
    {
        $agency = Agency::where('slug', $slug)
            ->where('is_verified', true)
            ->firstOrFail();

        $profileData = $this->agencyProfileService->getPublicProfile($agency);

        return view('public-pages.agency-profile', $profileData);
    }
}

// End of file