<?php
// File: app/Models/Booking.php
// Deskripsi: Booking model untuk pemesanan tiket

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Enums\BookingStatus;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_code',
        'schedule_id',
        'customer_id',
        'origin_stop_id',
        'destination_stop_id',
        'route_pricing_id',
        'pickup_address',
        'pickup_maps_link',
        'pickup_latitude',
        'pickup_longitude',
        'destination_address',
        'destination_maps_link',
        'destination_latitude',
        'destination_longitude',
        'total_passengers',
        'total_price',
        'base_price',
        'service_fee',
        'platform_fee',
        'discount_amount',
        'status',
        'special_notes',
        'e_ticket_url',
        'cancelled_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_passengers' => 'integer',
            'total_price' => 'decimal:2',
            'base_price' => 'decimal:2',
            'service_fee' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'pickup_latitude' => 'decimal:7',
            'pickup_longitude' => 'decimal:7',
            'destination_latitude' => 'decimal:7',
            'destination_longitude' => 'decimal:7',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected function canCancel(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Jika sudah cancelled/completed/on_going → tidak bisa
                if (in_array($this->status, ['cancelled', 'completed', 'on_going'])) {
                    return false;
                }
                
                // Pending & confirmed → masih bisa cancel
                if (in_array($this->status, ['pending', 'confirmed'])) {
                    return true;
                }
                
                // Jika paid → cek waktu keberangkatan
                if ($this->status === 'paid') {
                    // Hitung mundur ke keberangkatan
                    $departureDateTime = \Carbon\Carbon::parse(
                        $this->schedule->departure_date->format('Y-m-d') . ' ' . $this->schedule->departure_time
                    );
                    
                    $hoursUntilDeparture = now()->diffInHours($departureDateTime, false);
                    
                    // Hanya bisa cancel jika > 24 jam sebelum keberangkatan
                    return $hoursUntilDeparture > 24;
                }
                
                return false;
            },
        );
    }

    protected function cancellationFee(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->status !== 'paid') {
                    return 0;
                }
                return (int) round($this->total_price * 0.25);
            },
        );
    }

    protected function cancellationRefund(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->status !== 'paid') {
                    return 0;
                }
                return max(0, (int) $this->total_price - $this->cancellation_fee);
            },
        );
    }

    protected function needsRefundApproval(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->cancellation_refund < 100000) {
                    return false;
                }
                
                $hoursSinceBooking = $this->created_at->diffInHours(now());
                if ($hoursSinceBooking < 1) {
                    return false;
                }
                
                return true;
            },
        );
    }

    protected function refundStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->payment) return null;
                $paymentDetail = $this->payment->payment_detail ?? [];
                return $paymentDetail['refund']['status'] ?? null;
            },
        );
    }

    protected function isPaid(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'paid',
        );
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => BookingStatus::tryFrom($this->status)?->label() ?? $this->status,
        );
    }

    protected function statusColor(): Attribute
    {
        return Attribute::make(
            get: fn () => BookingStatus::tryFrom($this->status)?->color() ?? 'gray',
        );
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function originStop(): BelongsTo
    {
        return $this->belongsTo(RouteStop::class, 'origin_stop_id');
    }

    public function destinationStop(): BelongsTo
    {
        return $this->belongsTo(RouteStop::class, 'destination_stop_id');
    }

    public function routePricing(): BelongsTo
    {
        return $this->belongsTo(RoutePricing::class, 'route_pricing_id');
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(BookingPassenger::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function cashPayment(): HasOne
    {
        return $this->hasOne(CashPayment::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereHas('schedule', function (Builder $q) {
            $q->where('departure_date', '>=', now()->toDateString());
        });
    }

    public function scopeByAgency(Builder $query, int $agencyId): Builder
    {
        return $query->whereHas('schedule', function (Builder $q) use ($agencyId) {
            $q->where('agency_id', $agencyId);
        });
    }

    public function scopeByCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }
}

// End of file