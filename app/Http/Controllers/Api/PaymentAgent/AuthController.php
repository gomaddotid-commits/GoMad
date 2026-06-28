<?php
// File: app/Http/Controllers/Api/PaymentAgent/AuthController.php
// Deskripsi: API Controller untuk autentikasi payment agent

namespace App\Http\Controllers\Api\PaymentAgent;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = \App\Models\User::where('email', $request->email)
            ->where('role', 'payment_agent')
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
                'data' => null,
                'meta' => null,
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun dinonaktifkan.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        $agent = $user->paymentAgent;
        if (!$agent || !$agent->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Warung belum diverifikasi.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        $token = $user->createToken('payment-agent-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'agent' => [
                    'id' => $agent->id,
                    'agent_name' => $agent->agent_name,
                    'address' => $agent->address,
                ],
                'token' => $token,
            ],
            'meta' => null,
        ]);
    }
}

// End of file