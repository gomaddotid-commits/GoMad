<?php
// File: app/Http/Controllers/Web/Admin/SettlementController.php
// Deskripsi: Web Controller untuk manajemen settlement oleh admin

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Services\SettlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettlementController extends Controller
{
    public function __construct(
        private readonly SettlementService $settlementService,
    ) {}

    public function index(Request $request): View
    {
        $query = Settlement::with(['paymentAgent.user']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $settlements = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.settlements.index', compact('settlements'));
    }

    public function verify(Settlement $settlement): RedirectResponse
    {
        try {
            $this->settlementService->verifySettlement($settlement, auth()->user());
            return back()->with('success', 'Settlement diverifikasi.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

// End of file