<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $table = 'indonesia_cities';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code', 'province_code', 'name', 'meta'];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class, 'city_code', 'code');
    }

    public function agencies(): HasMany
    {
        return $this->hasMany(Agency::class, 'city_code', 'code');
    }

    public function routeStops(): HasMany
    {
        return $this->hasMany(RouteStop::class, 'city_code', 'code');
    }

    // Helper: dapatkan latitude dari meta
    public function getLatitudeAttribute(): ?float
    {
        return $this->meta['lat'] ?? null;
    }

    // Helper: dapatkan longitude dari meta
    public function getLongitudeAttribute(): ?float
    {
        return $this->meta['long'] ?? null;
    }
}