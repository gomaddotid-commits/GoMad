<?php
// File: app/Models/Promo.php
// Deskripsi: Model untuk promo (FIXED)

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'type', 'description',
        'discount_percent', 'max_discount', 'min_purchase',
        'route_id', 'travel_class',
        'applicable_payment_methods',
        'start_date', 'end_date',
        'cost_bearer', 'platform_share_percent', 'agency_share_percent',
        'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'discount_percent' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'min_purchase' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
            'platform_share_percent' => 'decimal:2',
            'agency_share_percent' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function schedules(): BelongsToMany
    {
        return $this->belongsToMany(Schedule::class, 'promo_schedule')->withTimestamps();
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PromoUsage::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeGeneral($query)
    {
        return $query->where('type', 'general');
    }

    public function scopeSelective($query)
    {
        return $query->where('type', 'selective');
    }

    public function scopeReferral($query)
    {
        return $query->where('type', 'referral');
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'referral' => '🎁 Referral',
            'general' => '🌍 General',
            'selective' => '🎯 Selektif',
            default => $this->type,
        };
    }

    public function getCostBearerLabelAttribute(): string
    {
        return match($this->cost_bearer) {
            'platform' => 'Platform',
            'agency' => 'Agency',
            'shared' => 'Shared (Platform ' . $this->platform_share_percent . '% + Agency ' . $this->agency_share_percent . '%)',
            default => $this->cost_bearer,
        };
    }

    public function isActiveNow(): bool
    {
        return $this->is_active 
            && now()->gte($this->start_date) 
            && now()->lte($this->end_date);
    }

    /**
     * Cek apakah promo berlaku untuk metode pembayaran tertentu
     */
    public function isApplicableFor(string $paymentMethod): bool
    {
        if (empty($this->applicable_payment_methods)) {
            return true;
        }
        
        $methods = $this->getApplicablePaymentMethodsArray();
        return in_array($paymentMethod, $methods);
    }

    /**
     * Get list metode pembayaran sebagai array (selalu return array)
     */
    public function getApplicablePaymentMethodsArray(): array
    {
        $value = $this->attributes['applicable_payment_methods'] ?? null;
        
        if (empty($value)) {
            return ['midtrans', 'cash', 'cod'];
        }
        
        if (is_array($value)) {
            return $value;
        }
        
        return explode(',', $value);
    }

    /**
     * Accessor untuk getApplicablePaymentMethodsAttribute (digunakan di view)
     */
    public function getApplicablePaymentMethodsAttribute(): array
    {
        return $this->getApplicablePaymentMethodsArray();
    }

    /**
     * Mutator: simpan array sebagai string
     */
    public function setApplicablePaymentMethodsAttribute($value): void
    {
        if (is_array($value)) {
            // Filter nilai kosong
            $value = array_filter($value);
            $this->attributes['applicable_payment_methods'] = !empty($value) ? implode(',', $value) : null;
        } elseif (is_string($value)) {
            $this->attributes['applicable_payment_methods'] = !empty($value) ? $value : null;
        } else {
            $this->attributes['applicable_payment_methods'] = null;
        }
    }
}

// End of file