<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    public function index(): JsonResponse
    {
        $promos = Promo::with('creator')->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Daftar promo berhasil diambil.',
            'data' => $promos,
            'meta' => [
                'current_page' => $promos->currentPage(),
                'last_page' => $promos->lastPage(),
                'total' => $promos->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:general,selective'],
            'discount_percent' => ['required', 'numeric', 'min:1', 'max:100'],
            'max_discount' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'cost_bearer' => ['required', 'in:platform,agency,shared'],
        ]);

        $data = $request->all();
        $data['created_by'] = $request->user()->id;
        $data['is_active'] = true;

        $promo = Promo::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil dibuat.',
            'data' => $promo,
            'meta' => null,
        ], 201);
    }

    public function show(Promo $promo): JsonResponse
    {
        $promo->load(['creator', 'route', 'usages']);

        return response()->json([
            'success' => true,
            'message' => 'Detail promo berhasil diambil.',
            'data' => $promo,
            'meta' => null,
        ]);
    }

    public function update(Request $request, Promo $promo): JsonResponse
    {
        $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'discount_percent' => ['sometimes', 'numeric', 'min:1', 'max:100'],
            'max_discount' => ['sometimes', 'numeric', 'min:0'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $promo->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil diupdate.',
            'data' => $promo->fresh(),
            'meta' => null,
        ]);
    }

    public function destroy(Promo $promo): JsonResponse
    {
        $promo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil dihapus.',
            'data' => null,
            'meta' => null,
        ]);
    }
}