<?php
// File: app/Http/Controllers/Api/Admin/PaymentAgentController.php
// Deskripsi: API Controller untuk manajemen payment agent oleh admin

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentAgent;
use App\Services\PaymentAgentService;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentAgentController extends Controller
{
    public function __construct(
        private readonly VerificationService $verificationService,
        private readonly PaymentAgentService $paymentAgentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = PaymentAgent::with('user');

        if ($request->has('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('agent_name', 'like', '%' . $request->search . '%')
                    ->orWhere('address', 'like', '%' . $request->search . '%')
                    ->orWhere('owner_name', 'like', '%' . $request->search . '%');
            });
        }

        $agents = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Daftar payment agent berhasil diambil.',
            'data' => $agents->map(function ($agent) {
                return [
                    'id' => $agent->id,
                    'agent_name' => $agent->agent_name,
                    'owner_name' => $agent->owner_name,
                    'owner_phone' => $agent->owner_phone,
                    'address' => $agent->address,
                    'kecamatan' => $agent->kecamatan,
                    'is_active' => $agent->is_active,
                    'is_verified' => $agent->is_verified,
                    'total_transactions' => $agent->total_transactions,
                    'total_commission' => (float) $agent->total_commission,
                    'balance_to_settle' => (float) $agent->balance_to_settle,
                    'created_at' => $agent->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'meta' => [
                'current_page' => $agents->currentPage(),
                'last_page' => $agents->lastPage(),
                'total' => $agents->total(),
            ],
        ]);
    }

    public function show(PaymentAgent $agent): JsonResponse
    {
        $agent->load(['user', 'settlements' => function ($query) {
            $query->latest()->limit(5);
        }]);

        $stats = $this->paymentAgentService->getAgentStats($agent);

        return response()->json([
            'success' => true,
            'message' => 'Detail payment agent berhasil diambil.',
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
                'stats' => $stats,
                'recent_settlements' => $agent->settlements->map(function ($s) {
                    return [
                        'id' => $s->id,
                        'period' => $s->period_start->format('d M') . ' - ' . $s->period_end->format('d M Y'),
                        'amount' => (float) $s->amount_to_settle,
                        'status' => $s->status,
                    ];
                }),
                'created_at' => $agent->created_at->format('Y-m-d H:i:s'),
            ],
            'meta' => null,
        ]);
    }

    public function verify(PaymentAgent $agent): JsonResponse
    {
        $this->verificationService->verifyPaymentAgent($agent, request()->user());

        return response()->json([
            'success' => true,
            'message' => 'Payment agent berhasil diverifikasi.',
            'data' => null,
            'meta' => null,
        ]);
    }

    public function reject(Request $request, PaymentAgent $agent): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->verificationService->rejectPaymentAgent($agent, request()->user(), $request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Payment agent ditolak.',
            'data' => null,
            'meta' => null,
        ]);
    }

    public function toggleActive(PaymentAgent $agent): JsonResponse
    {
        $this->paymentAgentService->toggleActive($agent);

        return response()->json([
            'success' => true,
            'message' => 'Status payment agent berhasil diubah.',
            'data' => ['is_active' => $agent->fresh()->is_active],
            'meta' => null,
        ]);
    }
}

// End of file