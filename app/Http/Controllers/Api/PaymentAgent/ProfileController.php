<?php
// File: app/Http/Controllers/Api/PaymentAgent/ProfileController.php
// Deskripsi: API Controller untuk profil payment agent

namespace App\Http\Controllers\Api\PaymentAgent;

use App\Http\Controllers\Controller;
use App\Services\PaymentAgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private readonly PaymentAgentService $paymentAgentService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $agent = $request->user()->paymentAgent;

        return response()->json([
            'success' => true,
            'message' => 'Profil warung berhasil diambil.',
            'data' => [
                'id' => $agent->id,
                'agent_name' => $agent->agent_name,
                'owner_name' => $agent->owner_name,
                'owner_phone' => $agent->owner_phone,
                'guard_name' => $agent->guard_name,
                'guard_phone' => $agent->guard_phone,
                'address' => $agent->address,
                'kecamatan' => $agent->kecamatan,
                'maps_link' => $agent->maps_link,
                'latitude' => $agent->latitude ? (float) $agent->latitude : null,
                'longitude' => $agent->longitude ? (float) $agent->longitude : null,
                'photo_warung' => $agent->photo_warung ? asset('storage/' . $agent->photo_warung) : null,
                'photo_ktp_owner' => $agent->photo_ktp_owner ? asset('storage/' . $agent->photo_ktp_owner) : null,
                'is_active' => $agent->is_active,
                'is_verified' => $agent->is_verified,
                'commission_rate' => (float) $agent->commission_rate,
                'total_transactions' => $agent->total_transactions,
            ],
            'meta' => null,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'agent_name' => ['sometimes', 'string', 'max:100'],
            'owner_name' => ['sometimes', 'string', 'max:100'],
            'owner_phone' => ['sometimes', 'string', 'max:20'],
            'guard_name' => ['nullable', 'string', 'max:100'],
            'guard_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['sometimes', 'string', 'max:500'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'maps_link' => ['nullable', 'url', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $agent = $request->user()->paymentAgent;

        $agent = $this->paymentAgentService->updateAgent($agent, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Profil warung berhasil diupdate.',
            'data' => [
                'id' => $agent->id,
                'agent_name' => $agent->agent_name,
                'address' => $agent->address,
            ],
            'meta' => null,
        ]);
    }

    public function updatePin(Request $request): JsonResponse
    {
        $request->validate([
            'current_pin' => ['required', 'string', 'size:6'],
            'new_pin' => ['required', 'string', 'size:6', 'different:current_pin'],
        ]);

        $agent = $request->user()->paymentAgent;

        try {
            $this->paymentAgentService->updatePin(
                $agent,
                $request->current_pin,
                $request->new_pin
            );

            return response()->json([
                'success' => true,
                'message' => 'PIN berhasil diubah.',
                'data' => null,
                'meta' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'meta' => null,
            ], 422);
        }
    }
}

// End of file