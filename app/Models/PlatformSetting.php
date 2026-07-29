<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class PlatformSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description',
        'updated_by',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return Cache::remember("platform_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function setValue(string $key, mixed $value, ?int $userId = null): void
    {
        // ✅ GUNAKAN LOCK untuk mencegah race condition
        Cache::lock("platform_setting_lock_{$key}", 5)->block(3, function () use ($key, $value, $userId) {
            $setting = self::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'updated_by' => $userId,
                ]
            );

            // ✅ Hapus individual cache DAN all-settings cache
            Cache::forget("platform_setting_{$key}");
            Cache::forget('platform_settings_all');
            
            \Log::info('Platform setting updated', [
                'key' => $key,
                'updated_by' => $userId,
            ]);
        });
    }

    public static function getAllSettings(): array
    {
        return Cache::remember('platform_settings_all', 3600, function () {
            return self::pluck('value', 'key')->toArray();
        });
    }

    public static function clearCache(): void
    {
        $keys = self::pluck('key')->toArray();
        foreach ($keys as $key) {
            Cache::forget("platform_setting_{$key}");
        }
        Cache::forget('platform_settings_all');
        
        \Log::info('All platform settings cache cleared');
    }
}