<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AgencyPolicy;
use App\Models\AgencyWallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgencyPolicyController extends Controller
{
    // Daftar semua agency + status policy
    public function index(Request $request): View
    {
        $agencies = Agency::with(['policy', 'wallet'])
            ->when($request->search, fn($q) => $q->where('agency_name', 'like', "%{$request->search}%"))
            ->when($request->has_policy, fn($q) => $request->has_policy == 1
                ? $q->has('policy')
                : $q->doesntHave('policy'))
            ->withCount('bookings')
            ->orderBy('agency_name')
            ->paginate(15);

        return view('admin.agency-policies.index', compact('agencies'));
    }

    // Form edit policy untuk agency tertentu
    public function edit(Agency $agency): View
    {
        $policy = $agency->policy ?? new AgencyPolicy(['agency_id' => $agency->id]);
        $wallet = $agency->wallet;

        return view('admin.agency-policies.edit', compact('agency', 'policy', 'wallet'));
    }

    // Simpan/update policy
    public function update(Request $request, Agency $agency): RedirectResponse
    {
        $validated = $request->validate([
            'allow_cod_without_deposit' => ['nullable', 'boolean'],
            'cod_min_balance' => ['nullable', 'numeric', 'min:0'],
            'cod_daily_limit' => ['nullable', 'numeric', 'min:0'],
            'cod_max_per_booking' => ['nullable', 'numeric', 'min:0'],
            'allow_ots' => ['nullable', 'boolean'],
            'ots_deposit_required' => ['nullable', 'boolean'],
            'commission_override' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'settlement_schedule' => ['nullable', 'in:daily,weekly,monthly'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['allow_cod_without_deposit'] = $request->boolean('allow_cod_without_deposit');
        $validated['allow_ots'] = $request->boolean('allow_ots');
        $validated['ots_deposit_required'] = $request->boolean('ots_deposit_required');
        $validated['commission_override'] = $validated['commission_override'] ?? null;

        AgencyPolicy::updateOrCreate(
            ['agency_id' => $agency->id],
            $validated
        );

        return redirect()->route('admin.agency-policies.index')
            ->with('success', "Kebijakan untuk {$agency->agency_name} berhasil disimpan!");
    }

    // Hapus policy (kembali ke default global)
    public function destroy(Agency $agency): RedirectResponse
    {
        $agency->policy?->delete();

        return redirect()->route('admin.agency-policies.index')
            ->with('success', "Kebijakan {$agency->agency_name} dihapus. Kembali ke default global.");
    }
}
