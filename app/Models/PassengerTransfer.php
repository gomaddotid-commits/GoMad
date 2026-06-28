<?php
// File: app/Models/PassengerTransfer.php
// Deskripsi: Model untuk transfer penumpang antar jadwal

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PassengerTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_schedule_id',
        'to_schedule_id',
        'from_agency_id',
        'to_agency_id',
        'total_passengers',
        'transfer_fee_per_passenger',
        'total_transfer_fee',
        'total_booking_value',
        'status',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_passengers' => 'integer',
            'transfer_fee_per_passenger' => 'decimal:2',
            'total_transfer_fee' => 'decimal:2',
            'total_booking_value' => 'decimal:2',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function fromSchedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'from_schedule_id');
    }

    public function toSchedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'to_schedule_id');
    }

    public function fromAgency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'from_agency_id');
    }

    public function toAgency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'to_agency_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'passenger_transfer_bookings')
            ->withTimestamps();
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where(function ($q) use ($agencyId) {
            $q->where('from_agency_id', $agencyId)
              ->orWhere('to_agency_id', $agencyId);
        });
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => '⏳ Menunggu',
            'approved' => '✅ Disetujui',
            'rejected' => '❌ Ditolak',
            'completed' => '🎉 Selesai',
            'cancelled' => '🚫 Dibatalkan',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'approved' => 'blue',
            'rejected' => 'red',
            'completed' => 'green',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }
}

// End of file