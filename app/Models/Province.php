<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    protected $table = 'indonesia_provinces';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code', 'name', 'meta'];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'province_code', 'code');
    }

    public function agencies(): HasMany
    {
        return $this->hasMany(Agency::class, 'province_code', 'code');
    }

    public function getNameAttribute($value): string
    {
        if (empty($value)) return 'Unknown';
        if ($value === ucwords(strtolower($value))) return $value;
        return ucwords(strtolower($value));
    }
}