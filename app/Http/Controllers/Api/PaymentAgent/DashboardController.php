<?php
// File: app/Http/Controllers/Api/PaymentAgent/DashboardController.php
// Deskripsi: API Controller untuk dashboard payment agent

namespace App\Http\Controllers\Api\PaymentAgent;

use App\Http\Controllers\Controller;
use App\Services\PaymentAgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly PaymentAgentService $paymentAgentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $agent = $request->user()->paymentAgent;

        $stats = $this->paymentAgentService->getAgentStats($agent);

        return response()->json([
            'success' => true,
            'message' => 'Data dashboard berhasil diambil.',
            'data' => [
                'agent' => [
                    'id' => $agent->id,
                    'agent_name' => $agent->agent_name,
                    'is_active' => $agent->is_active,
                    'is_verified' => $agent->is_verified,
                ],
                'stats' => $stats,
            ],
            'meta' => null,
        ]);
    }
}

// End of file