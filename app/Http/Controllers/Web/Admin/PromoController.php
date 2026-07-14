<?php
// File: app/Http/Controllers/Web/Admin/PromoController.php
// Deskripsi: Web Controller untuk manajemen promo oleh admin

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use App\Models\Route;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromoController extends Controller
{
    public function index(): View
    {
        $promos = Promo::with('creator')->latest()->paginate(15);
        return view('admin.promos.index', compact('promos'));
    }

    public function create(): View
    {
        $routes = Route::where('is_active', true)->get();
        return view('admin.promos.create', compact('routes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:general,selective'],
            'module' => ['required', 'in:travel,rental,all'],
            'description' => ['nullable', 'string', 'max:500'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'cost_bearer' => ['required', 'in:platform,agency,shared'],
        ];

        $module = $request->module;

        // Travel discount fields
        if ($module === 'travel' || $module === 'all') {
            $rules['discount_percent'] = ['required', 'numeric', 'min:1', 'max:100'];
            $rules['max_discount'] = ['required', 'numeric', 'min:0'];
            $rules['min_purchase'] = ['nullable', 'numeric', 'min:0'];
        }

        // Rental discount fields
        if ($module === 'rental' || $module === 'all') {
            $rules['rental_discount_type'] = ['required', 'in:percent,fixed'];
            $rules['rental_discount_amount'] = ['required', 'numeric', 'min:0'];
            $rules['rental_max_discount'] = ['nullable', 'numeric', 'min:0'];
        }

        $request->validate($rules);

        $data = $request->all();
        $data['created_by'] = auth()->id();
        $data['is_active'] = true;

        // 👇 SET DEFAULT UNTUK FIELD YANG TIDAK DIKIRIM
        if (!isset($data['discount_percent']) || $data['discount_percent'] === null || $data['discount_percent'] === '') {
            $data['discount_percent'] = 0;
        }
        if (!isset($data['max_discount']) || $data['max_discount'] === null || $data['max_discount'] === '') {
            $data['max_discount'] = 0;
        }
        if (!isset($data['min_purchase']) || $data['min_purchase'] === null || $data['min_purchase'] === '') {
            $data['min_purchase'] = 0;
        }

        // Proses applicable_payment_methods
        if ($request->has('applicable_payment_methods') && !empty($request->applicable_payment_methods)) {
            $data['applicable_payment_methods'] = implode(',', $request->applicable_payment_methods);
        } else {
            $data['applicable_payment_methods'] = null;
        }

        // Set share percentages
        if ($data['cost_bearer'] === 'platform') {
            $data['platform_share_percent'] = 100;
            $data['agency_share_percent'] = 0;
        } elseif ($data['cost_bearer'] === 'agency') {
            $data['platform_share_percent'] = 0;
            $data['agency_share_percent'] = 100;
        } elseif ($data['cost_bearer'] === 'shared') {
            $data['platform_share_percent'] = $request->platform_share ?? 50;
            $data['agency_share_percent'] = $request->agency_share ?? 50;
        }

        Promo::create($data);

        return redirect()->route('admin.promos.index')
            ->with('success', 'Promo berhasil dibuat!');
    }

    public function update(Request $request, Promo $promo): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ];

        $module = $request->module ?? $promo->module;

        if ($module === 'travel' || $module === 'all') {
            $rules['discount_percent'] = ['required', 'numeric', 'min:1', 'max:100'];
            $rules['max_discount'] = ['required', 'numeric', 'min:0'];
            $rules['min_purchase'] = ['nullable', 'numeric', 'min:0'];
        }

        if ($module === 'rental' || $module === 'all') {
            $rules['rental_discount_type'] = ['required', 'in:percent,fixed'];
            $rules['rental_discount_amount'] = ['required', 'numeric', 'min:0'];
            $rules['rental_max_discount'] = ['nullable', 'numeric', 'min:0'];
        }

        $request->validate($rules);

        $data = $request->all();

        // 👇 SET DEFAULT
        if (!isset($data['discount_percent']) || $data['discount_percent'] === null || $data['discount_percent'] === '') {
            $data['discount_percent'] = $promo->discount_percent ?? 0;
        }
        if (!isset($data['max_discount']) || $data['max_discount'] === null || $data['max_discount'] === '') {
            $data['max_discount'] = $promo->max_discount ?? 0;
        }
        if (!isset($data['min_purchase']) || $data['min_purchase'] === null || $data['min_purchase'] === '') {
            $data['min_purchase'] = $promo->min_purchase ?? 0;
        }

        // Proses applicable_payment_methods
        if ($request->has('applicable_payment_methods') && !empty($request->applicable_payment_methods)) {
            $data['applicable_payment_methods'] = implode(',', $request->applicable_payment_methods);
        } else {
            $data['applicable_payment_methods'] = null;
        }

        $promo->update($data);

        return redirect()->route('admin.promos.index')
            ->with('success', 'Promo berhasil diupdate!');
    }

    public function show(Promo $promo): View
    {
        $promo->load(['creator', 'route', 'usages.booking', 'schedules.agency']);
        return view('admin.promos.show', compact('promo'));
    }

    public function edit(Promo $promo): View
    {
        $routes = Route::where('is_active', true)->get();
        return view('admin.promos.edit', compact('promo', 'routes'));
    }

    public function destroy(Promo $promo): RedirectResponse
    {
        $promo->delete();
        return back()->with('success', 'Promo berhasil dihapus.');
    }
}

// End of file