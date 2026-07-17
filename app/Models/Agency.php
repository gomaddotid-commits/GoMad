<?php

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
        'province_code',      // FK ke indonesia_provinces
        'city_code',          // FK ke indonesia_cities
        'district_code',      // FK ke indonesia_districts
        'address',
        'latitude',
        'longitude',
        'coverage_cities',    // JSON array of city_codes
        'logo',
        'cover_image',
        'business_license',
        'description',
        'founded_year',
        'fleet_size',
        'services',
        'social_media',
        'business_hours',
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
            'coverage_cities' => 'array',
            'services' => 'array',
            'social_media' => 'array',
            'business_hours' => 'array',
            'gallery' => 'array',
            'is_verified' => 'boolean',
            'rating' => 'decimal:2',
            'founded_year' => 'integer',
            'fleet_size' => 'integer',
            'total_bookings' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    // ═══════════════════════════════════════
    // RELASI KE LARAVOLT
    // ═══════════════════════════════════════

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_code', 'code');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }

    // ═══════════════════════════════════════
    // RELASI KE TABEL LAIN
    // ═══════════════════════════════════════

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

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    // ═══════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════

    public function getProvinceNameAttribute(): string
    {
        return $this->province?->name ?? '-';
    }

    public function getCityNameAttribute(): string
    {
        return $this->city?->name ?? '-';
    }

    public function getDistrictNameAttribute(): string
    {
        return $this->district?->name ?? '-';
    }

    public function getFullAddressAttribute(): string
    {
        $parts = [];
        if ($this->address) $parts[] = $this->address;
        if ($this->district_name !== '-') $parts[] = 'Kec. ' . $this->district_name;
        $parts[] = $this->city_name;
        $parts[] = $this->province_name;
        return implode(', ', $parts);
    }

    public function getCoverageCitiesListAttribute(): array
    {
        if (empty($this->coverage_cities)) {
            return [];
        }

        return City::whereIn('code', $this->coverage_cities)
            ->get()
            ->map(fn($city) => [
                'code' => $city->code,
                'name' => $city->name,
                'province' => $city->province?->name,
            ])
            ->toArray();
    }

    public function getAverageRatingAttribute(): float
    {
        return (float) $this->rating;
    }

    public function getProfileCompletePercentageAttribute(): int
    {
        $fields = ['logo', 'cover_image', 'business_license', 'address', 'description',
                   'founded_year', 'contact_person', 'province_code', 'city_code'];
        $filled = 0;
        foreach ($fields as $field) {
            if (!empty($this->$field)) $filled++;
        }
        return (int) round(($filled / count($fields)) * 100);
    }

    // ═══════════════════════════════════════
    // VALIDASI COVERAGE
    // ═══════════════════════════════════════

    public function servesCity(string $cityCode): bool
    {
        if (empty($this->coverage_cities)) {
            // Jika coverage tidak di-set, anggap hanya melayani kota sendiri
            return $this->city_code === $cityCode;
        }
        return in_array($cityCode, $this->coverage_cities);
    }

    public function servesRoute(Route $route): bool
    {
        $allCityCodes = $route->stops->pluck('city_code')->toArray();
        foreach ($allCityCodes as $cityCode) {
            if (!$this->servesCity($cityCode)) {
                return false;
            }
        }
        return true;
    }

    // ═══════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeUnverified(Builder $query): Builder
    {
        return $query->where('is_verified', false);
    }

    public function scopeInProvince(Builder $query, string $provinceCode): Builder
    {
        return $query->where('province_code', $provinceCode);
    }

    public function scopeInCity(Builder $query, string $cityCode): Builder
    {
        return $query->where('city_code', $cityCode);
    }

    public function scopeInDistrict(Builder $query, string $districtCode): Builder
    {
        return $query->where('district_code', $districtCode);
    }

    public function scopeNearby(Builder $query, float $lat, float $lng, float $radiusKm = 50): Builder
    {
        return $query->selectRaw("
            agencies.*,
            (6371 * acos(
                cos(radians(?)) * cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + 
                sin(radians(?)) * sin(radians(latitude))
            )) AS distance
        ", [$lat, $lng, $lat])
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->having('distance', '<=', $radiusKm)
        ->orderBy('distance');
    }

    public function scopeByRating(Builder $query, float $minRating): Builder
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }
}