<?php
// File: app/Models/User.php
// Deskripsi: User model untuk authentication dan profile management dengan 5 role

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'agency_id',
        'avatar_url',
        'referred_by',
        'phone_verified_at',
        'last_location',
        'preferences',
        'is_active',
        'banned_at',
        'banned_reason',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_location' => 'array',
            'preferences' => 'array',
            'is_active' => 'boolean',
            'banned_at' => 'datetime',
        ];
    }

    protected function isAgency(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->role === 'agency',
        );
    }

    protected function isCustomer(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->role === 'customer',
        );
    }

    protected function isAdmin(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->role === 'admin',
        );
    }

    protected function isDriver(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->role === 'driver',
        );
    }

    protected function isPaymentAgent(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->role === 'payment_agent',
        );
    }

    public function agency(): HasOne
    {
        return $this->hasOne(Agency::class);
    }

    public function agencyBelongTo(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function driverAgency(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function agencyVerification(): HasOne
    {
        return $this->hasOne(AgencyVerification::class, 'verified_by');
    }

    public function driverSchedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'driver_id');
    }

    public function driverLocations(): HasMany
    {
        return $this->hasMany(DriverLocation::class, 'driver_id');
    }

    public function customerBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'customer_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function paymentAgent(): HasOne
    {
        return $this->hasOne(PaymentAgent::class);
    }

    public function approvedWithdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class, 'approved_by');
    }

    public function verifiedSettlements(): HasMany
    {
        return $this->hasMany(Settlement::class, 'verified_by');
    }

    public function updatedPlatformSettings(): HasMany
    {
        return $this->hasMany(PlatformSetting::class, 'updated_by');
    }

    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    public function scopeCustomers(Builder $query): Builder
    {
        return $query->where('role', 'customer');
    }

    public function scopeAgencies(Builder $query): Builder
    {
        return $query->where('role', 'agency');
    }

    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('role', 'admin');
    }

    public function scopeDrivers(Builder $query): Builder
    {
        return $query->where('role', 'driver');
    }

    public function scopePaymentAgents(Builder $query): Builder
    {
        return $query->where('role', 'payment_agent');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByAgency(Builder $query, int $agencyId): Builder
    {
        return $query->where('agency_id', $agencyId);
    }
}

// End of file