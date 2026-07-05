<?php
// File: app/Http/Controllers/Api/Auth/LoginController.php
// Deskripsi: API Controller untuk autentikasi login

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dinonaktifkan. Hubungi admin.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        if ($user->banned_at) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dibanned: ' . ($user->banned_reason ?? 'Tidak ada alasan'),
                'data' => null,
                'meta' => null,
            ], 403);
        }

        $token = $user->createToken($request->device_name ?? 'api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'avatar_url' => $user->avatar_url,
                    'agency_id' => $user->agency_id,
                    'is_active' => $user->is_active,
                ],
                'token' => $token,
            ],
            'meta' => null,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'avatar_url' => $user->avatar_url,
            'agency_id' => $user->agency_id,
            'is_active' => $user->is_active,
            'phone_verified_at' => $user->phone_verified_at?->format('Y-m-d H:i:s'),
            'created_at' => $user->created_at->format('Y-m-d H:i:s'),
        ];

        if ($user->role === 'agency' && $user->agency) {
            $data['agency'] = [
                'id' => $user->agency->id,
                'agency_name' => $user->agency->agency_name,
                'slug' => $user->agency->slug,
                'is_verified' => $user->agency->is_verified,
                'logo' => $user->agency->logo ?? null,
            ];
        }

        if ($user->role === 'payment_agent' && $user->paymentAgent) {
            $data['payment_agent'] = [
                'id' => $user->paymentAgent->id,
                'agent_name' => $user->paymentAgent->agent_name,
                'is_verified' => $user->paymentAgent->is_verified,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Data user berhasil diambil.',
            'data' => $data,
            'meta' => null,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'avatar_url' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $user->update($request->only(['name', 'phone', 'avatar_url']));

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diupdate.',
            'data' => $user->only(['id', 'name', 'email', 'phone', 'avatar_url']),
            'meta' => null,
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password saat ini salah.',
                'data' => null,
                'meta' => null,
            ], 422);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah.',
            'data' => null,
            'meta' => null,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
            'data' => null,
            'meta' => null,
        ]);
    }
}

// End of file