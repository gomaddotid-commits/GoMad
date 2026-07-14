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

    public function isCompleteForSelfDrive(): bool
    {
        return $this->ktp_verified && $this->sim_verified;
    }

    public function isCompleteForRental(): bool
    {
        return $this->ktp_verified && $this->sim_verified;
    }
}