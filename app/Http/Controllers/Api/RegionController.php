<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    /**
     * Dapatkan semua provinsi
     */
    public function provinces(): JsonResponse
    {
        $provinces = Province::orderBy('name')
            ->get(['code', 'name']);

        return response()->json([
            'success' => true,
            'data' => $provinces,
        ]);
    }

    /**
     * Dapatkan kota berdasarkan provinsi
     */
    public function cities(Request $request): JsonResponse
    {
        $request->validate([
            'province' => ['required', 'string', 'exists:indonesia_provinces,code'],
        ]);

        $cities = City::where('province_code', $request->province)
            ->orderBy('name')
            ->get(['code', 'name', 'province_code']);

        return response()->json([
            'success' => true,
            'data' => $cities,
        ]);
    }

    /**
     * Dapatkan kecamatan berdasarkan kota
     */
    public function districts(Request $request): JsonResponse
    {
        $request->validate([
            'city' => ['required', 'string', 'exists:indonesia_cities,code'],
        ]);

        $districts = District::where('city_code', $request->city)
            ->orderBy('name')
            ->get(['code', 'name', 'city_code']);

        return response()->json([
            'success' => true,
            'data' => $districts,
        ]);
    }

    /**
     * Search kota (autocomplete)
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $q = $request->q;

        $cities = City::with('province')
            ->where('name', 'like', "%{$q}%")
            ->limit(15)
            ->get()
            ->map(fn($city) => [
                'code' => $city->code,
                'name' => $city->name,
                'province' => $city->province?->name,
                'full_name' => "{$city->name}, {$city->province?->name}",
            ]);

        return response()->json([
            'success' => true,
            'data' => $cities,
        ]);
    }

    /**
     * Dapatkan semua kota (untuk dropdown)
     */
    public function allCities(): JsonResponse
    {
        $cities = City::with('province')
            ->orderBy('name')
            ->get()
            ->map(fn($city) => [
                'code' => $city->code,
                'name' => $city->name,
                'province' => $city->province?->name,
                'latitude' => $city->latitude,
                'longitude' => $city->longitude,
            ]);

        return response()->json([
            'success' => true,
            'data' => $cities,
        ]);
    }
}