<?php
// File: app/Models/PickupZone.php
// Deskripsi: PickupZone model untuk zona penjemputan agency

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'zone_name',
        'kecamatan',
        'additional_fee',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'additional_fee' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeByKecamatan($query, string $kecamatan)
    {
        return $query->where('kecamatan', $kecamatan);
    }
}

// End of file