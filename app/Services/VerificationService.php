<?php
// File: app/Services/VerificationService.php
// Deskripsi: Service untuk verifikasi agency dan payment agent

namespace App\Services;

use App\Models\Agency;
use App\Models\AgencyVerification;
use App\Models\PaymentAgent;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VerificationService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    // ==================== AGENCY ====================
    
    public function submitVerification(Agency $agency): AgencyVerification
    {
        $pendingVerification = AgencyVerification::where('agency_id', $agency->id)
            ->where('status', 'pending')
            ->first();
        
        if ($pendingVerification) {
            return $pendingVerification;
        }

        return AgencyVerification::create([
            'agency_id' => $agency->id,
            'status' => 'pending',
        ]);
    }

    public function approveVerification(Agency $agency, User $admin): void
    {
        DB::transaction(function () use ($agency, $admin) {
            $verification = AgencyVerification::where('agency_id', $agency->id)
                ->where('status', 'pending')
                ->first();
            
            if (!$verification) {
                $verification = AgencyVerification::create([
                    'agency_id' => $agency->id,
                    'status' => 'approved',
                    'verified_by' => $admin->id,
                    'verified_at' => now(),
                ]);
            } else {
                $verification->update([
                    'status' => 'approved',
                    'verified_by' => $admin->id,
                    'verified_at' => now(),
                ]);
            }
            
            $agency->update(['is_verified' => true]);
            $this->notificationService->agencyVerified($agency);
        });
    }

    public function rejectVerification(Agency $agency, User $admin, string $reason): void
    {
        DB::transaction(function () use ($agency, $admin, $reason) {
            $verification = AgencyVerification::where('agency_id', $agency->id)
                ->where('status', 'pending')
                ->first();
            
            if (!$verification) {
                $verification = AgencyVerification::create([
                    'agency_id' => $agency->id,
                    'status' => 'rejected',
                    'verified_by' => $admin->id,
                    'verified_at' => now(),
                    'rejection_reason' => $reason,
                ]);
            } else {
                $verification->update([
                    'status' => 'rejected',
                    'verified_by' => $admin->id,
                    'verified_at' => now(),
                    'rejection_reason' => $reason,
                ]);
            }
            
            $this->notificationService->agencyRejected($agency, $reason);
        });
    }

    public function getPendingAgencyVerifications(): Collection
    {
        return AgencyVerification::with(['agency.user', 'agency'])
            ->where('status', 'pending')
            ->latest()
            ->get();
    }

    public function getVerificationHistory(Agency $agency): Collection
    {
        return AgencyVerification::with('verifier')
            ->where('agency_id', $agency->id)
            ->latest()
            ->get();
    }

    // ==================== PAYMENT AGENT ====================
    
    /**
     * Verifikasi payment agent
     */
    public function verifyPaymentAgent(PaymentAgent $agent, User $admin): void
    {
        $agent->update([
            'is_verified' => true,
            'verified_by' => $admin->id,
            'verified_at' => now(),
            'rejection_reason' => null, // Hapus alasan penolakan sebelumnya
        ]);
        
        $this->notificationService->sendWhatsApp(
            $agent->owner_phone,
            "🎉 Selamat! Warung GoMad *{$agent->agent_name}* telah diverifikasi.\n\n" .
            "Anda sekarang dapat:\n" .
            "✅ Menerima pembayaran dari customer\n" .
            "✅ Konfirmasi pembayaran dengan kode bayar\n" .
            "✅ Melihat riwayat transaksi\n\n" .
            "Login ke aplikasi Warung GoMad untuk memulai."
        );
    }

    /**
     * Tolak payment agent dengan alasan
     */
    public function rejectPaymentAgent(PaymentAgent $agent, User $admin, string $reason): void
    {
        $agent->update([
            'is_verified' => false,
            'rejection_reason' => $reason,
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);
        
        $this->notificationService->sendWhatsApp(
            $agent->owner_phone,
            "❌ Maaf, pendaftaran Warung GoMad *{$agent->agent_name}* ditolak.\n\n" .
            "📝 *Alasan:* {$reason}\n\n" .
            "Silakan perbaiki data Anda dan setup ulang melalui aplikasi Warung GoMad.\n" .
            "Login dan klik 'Setup Ulang Profil Warung' untuk mengajukan kembali."
        );
    }

    /**
     * Cek status verifikasi payment agent (untuk tampilan di profil)
     */
    public function getPaymentAgentVerificationStatus(PaymentAgent $agent): array
    {
        return [
            'is_verified' => $agent->is_verified,
            'rejection_reason' => $agent->rejection_reason,
            'verified_at' => $agent->verified_at ? $agent->verified_at->format('d M Y H:i') : null,
            'verified_by' => $agent->verifier ? $agent->verifier->name : null,
        ];
    }

    public function getPendingPaymentAgents(): Collection
    {
        return PaymentAgent::with('user')
            ->where('is_verified', false)
            ->where('is_active', true)
            ->whereNotNull('agent_name') // Sudah mengisi data
            ->latest()
            ->get();
    }
}

// End of file