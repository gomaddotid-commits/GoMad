<?php
// File: app/Http/Controllers/Api/Auth/RegisterController.php
// Deskripsi: API Controller untuk registrasi user

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AgencyProfileService;
use App\Services\PaymentAgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function __construct(
        private readonly AgencyProfileService $agencyProfileService,
        private readonly PaymentAgentService $paymentAgentService,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'], // 👈 TAMBAH
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:customer'],
            'referral_code' => ['nullable', 'string', 'exists:referral_codes,code'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $token = $user->createToken('register-token')->plainTextToken;

        try {
            app(\App\Services\NotificationService::class)->welcomeCustomer($user);
        } catch (\Exception $e) {
            \Log::error('Welcome WhatsApp failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil.',
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
                    'phone_verified_at' => $user->phone_verified_at?->format('Y-m-d H:i:s'),
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                ],
                'token' => $token,
            ],
            'meta' => null,
        ], 201);
    }

    public function registerAgency(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'agency_name' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
            'contact_person' => ['required', 'string', 'max:100'],
        ]);

        $result = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => 'agency',
                'is_active' => true,
            ]);

            $slug = $this->agencyProfileService->generateSlug($request->agency_name);

            $agency = $user->agency()->create([
                'agency_name' => $request->agency_name,
                'slug' => $slug,
                'address' => $request->address,
                'contact_person' => $request->contact_person,
                'contact_alternate' => $request->contact_alternate ?? null,
                'email_alternate' => $request->email_alternate ?? null,
                'description' => $request->description ?? null,
                'is_verified' => false,
            ]);

            // 👇 KIRIM WHATSAPP WELCOME
            try {
                app(\App\Services\NotificationService::class)->welcomeAgency($result['user'], $result['agency']);
            } catch (\Exception $e) {
                \Log::error('Welcome WhatsApp failed: ' . $e->getMessage());
            }

            return ['user' => $user, 'agency' => $agency];
        });

        $token = $result['user']->createToken('agency-register-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi agency berhasil. Silakan lengkapi profil dan ajukan verifikasi.',
            'data' => [
                'user' => [
                    'id' => $result['user']->id,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                    'role' => $result['user']->role,
                ],
                'agency' => [
                    'id' => $result['agency']->id,
                    'agency_name' => $result['agency']->agency_name,
                    'slug' => $result['agency']->slug,
                ],
                'token' => $token,
            ],
            'meta' => null,
        ], 201);
    }

    public function registerPaymentAgent(Request $request): JsonResponse
    {
        $request->validate([
            'agent_name' => ['required', 'string', 'max:100'],
            'owner_name' => ['required', 'string', 'max:100'],
            'owner_phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'address' => ['required', 'string', 'max:500'],
            'pin' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
        ]);

        $agent = $this->paymentAgentService->registerAgent($request->all());
        
        try {
            app(\App\Services\NotificationService::class)->welcomePaymentAgent($agent->user, $agent);
        } catch (\Exception $e) {
            \Log::error('Welcome WhatsApp failed: ' . $e->getMessage());
        }
        
        $token = $agent->user->createToken('payment-agent-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran Warung GoMad berhasil. Menunggu verifikasi admin.',
            'data' => [
                'user' => [
                    'id' => $agent->user->id,
                    'name' => $agent->user->name,
                    'email' => $agent->user->email,
                    'role' => $agent->user->role,
                ],
                'agent' => [
                    'id' => $agent->id,
                    'agent_name' => $agent->agent_name,
                ],
                'token' => $token,
            ],
            'meta' => null,
        ], 201);
    }
}

// End of file