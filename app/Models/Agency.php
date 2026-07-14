<?php
// File: app/Models/Agency.php
// Deskripsi: Agency model (FIXED - accessor return type)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Agency extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'agency_name',
        'slug',
        'logo',
        'cover_image',
        'business_license',
        'address',
        'description',
        'founded_year',
        'fleet_size',
        'services',
        'social_media',
        'business_hours',
        'zone_coverage',
        'contact_person',
        'contact_alternate',
        'email_alternate',
        'gallery',
        'is_verified',
        'rating',
        'total_bookings',
    ];

    protected function casts(): array
    {
        return [
            'services' => 'json',
            'social_media' => 'json',
            'business_hours' => 'json',
            'zone_coverage' => 'json',
            'gallery' => 'json',
            'is_verified' => 'boolean',
            'rating' => 'decimal:2',
            'founded_year' => 'integer',
            'fleet_size' => 'integer',
            'total_bookings' => 'integer',
        ];
    }

    // HAPUS semua accessor get...Attribute() yang ada, ganti dengan ini:
    
    // Mutator untuk menyimpan sebagai JSON
    protected function services(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value === null) return [];
                if (is_array($value)) return $value;
                $decoded = json_decode($value, true);
                return is_array($decoded) ? $decoded : [];
            },
            set: function ($value) {
                if (is_array($value)) return json_encode($value);
                if (is_string($value)) return $value;
                return json_encode([]);
            }
        );
    }

    protected function socialMedia(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value === null) return [];
                if (is_array($value)) return $value;
                $decoded = json_decode($value, true);
                return is_array($decoded) ? $decoded : [];
            },
            set: function ($value) {
                if (is_array($value)) return json_encode($value);
                if (is_string($value)) return $value;
                return json_encode([]);
            }
        );
    }

    protected function businessHours(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value === null) return [];
                if (is_array($value)) return $value;
                $decoded = json_decode($value, true);
                return is_array($decoded) ? $decoded : [];
            },
            set: function ($value) {
                if (is_array($value)) return json_encode($value);
                if (is_string($value)) return $value;
                return json_encode([]);
            }
        );
    }

    protected function zoneCoverage(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value === null) return [];
                if (is_array($value)) return $value;
                $decoded = json_decode($value, true);
                return is_array($decoded) ? $decoded : [];
            },
            set: function ($value) {
                if (is_array($value)) return json_encode($value);
                if (is_string($value)) return $value;
                return json_encode([]);
            }
        );
    }

    protected function gallery(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value === null) return [];
                if (is_array($value)) return $value;
                $decoded = json_decode($value, true);
                return is_array($decoded) ? $decoded : [];
            },
            set: function ($value) {
                if (is_array($value)) return json_encode($value);
                if (is_string($value)) return $value;
                return json_encode([]);
            }
        );
    }

    protected function averageRating(): Attribute
    {
        return Attribute::make(
            get: fn () => (float) $this->rating,
        );
    }

    protected function totalCompletedBookings(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) $this->total_bookings,
        );
    }

    protected function profileCompletePercentage(): Attribute
    {
        return Attribute::make(
            get: function () {
                $fields = [
                    'logo', 'cover_image', 'business_license', 'address', 'description',
                    'founded_year', 'contact_person', 'contact_alternate', 'email_alternate',
                ];
                $filled = 0;
                foreach ($fields as $field) {
                    if (!empty($this->$field)) {
                        $filled++;
                    }
                }
                return (int) round(($filled / count($fields)) * 100);
            },
        );
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verification(): HasOne
    {
        return $this->hasOne(AgencyVerification::class)->latestOfMany();
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(AgencyVerification::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'driver');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function pickupZones(): HasMany
    {
        return $this->hasMany(PickupZone::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(AgencyWallet::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    // Scopes
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeUnverified(Builder $query): Builder
    {
        return $query->where('is_verified', false);
    }

    public function scopeByRating(Builder $query, float $minRating): Builder
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }
}

// End of file