<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ktp_number', 'ktp_photo', 'ktp_verified',
        'sim_number', 'sim_photo', 'sim_verified',
        'npwp_number', 'npwp_photo', 'npwp_verified',
        'selfie_photo', 'selfie_verified',
        'verification_status',
        'verified_by', 'verified_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'ktp_verified' => 'boolean',
            'sim_verified' => 'boolean',
            'npwp_verified' => 'boolean',
            'selfie_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Kelengkapan untuk rental lepas kunci (self-drive).
     * Syarat: KTP + SIM + Selfie sudah diverifikasi.
     * (NPWP tetap opsional, tapi ikut diserahkan ke agency saat masa sewa.)
     */
    public function isCompleteForSelfDrive(): bool
    {
        return $this->ktp_verified && $this->sim_verified && $this->selfie_verified;
    }

    public function isCompleteForRental(): bool
    {
        return $this->ktp_verified && $this->sim_verified && $this->selfie_verified;
    }

    /**
     * Semua dokumen inti sudah diupload (foto) — terlepas dari status verifikasi.
     */
    public function hasAllPhotos(): bool
    {
        return !empty($this->ktp_photo) && !empty($this->sim_photo) && !empty($this->selfie_photo);
    }
}