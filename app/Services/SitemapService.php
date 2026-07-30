<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\Route;
use App\Models\Promo;
use App\Models\VehicleRentalSetting;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class SitemapService
{
    /**
     * Generate sitemap.xml lengkap
     */
    public function generate(): string
    {
        $cacheKey = 'sitemap_content';
        
        return Cache::remember($cacheKey, 3600, function () {
            $urls = $this->getAllUrls();
            return $this->buildSitemapXml($urls);
        });
    }

    /**
     * Generate dan simpan ke storage
     */
    public function generateAndSave(): bool
    {
        $content = $this->generate();
        return Storage::disk('public')->put('sitemap.xml', $content);
    }

    /**
     * Clear cache sitemap
     */
    public function clearCache(): void
    {
        Cache::forget('sitemap_content');
    }

    /**
     * Kumpulkan semua URL
     */
    private function getAllUrls(): array
    {
        $urls = [];

        // 1. Static Pages
        $staticPages = [
            ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => route('search'), 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => route('listing'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => route('rental.public'), 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => route('eticket.public'), 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => route('download-app'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => route('login'), 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => route('register'), 'priority' => '0.5', 'changefreq' => 'monthly'],
        ];

        foreach ($staticPages as $page) {
            $urls[] = $page;
        }

        // 2. Agency Profiles
        $agencies = Agency::where('is_verified', true)
            ->select('id', 'slug', 'updated_at')
            ->get();

        foreach ($agencies as $agency) {
            $urls[] = [
                'loc' => route('agency.profile', $agency->slug),
                'priority' => '0.8',
                'changefreq' => 'weekly',
                'lastmod' => $agency->updated_at->toIso8601String(),
            ];
        }

        // 3. Routes - ✅ PERBAIKAN: Gunakan kolom yang ada di database
        // Route model memiliki kolom: id, route_name, origin_city_code, destination_city_code, updated_at
        // Untuk mendapatkan nama kota, join dengan tabel indonesia_cities
        $routes = DB::table('routes')
            ->join('indonesia_cities as origin', 'routes.origin_city_code', '=', 'origin.code')
            ->join('indonesia_cities as destination', 'routes.destination_city_code', '=', 'destination.code')
            ->where('routes.is_active', true)
            ->select(
                'routes.id',
                'routes.route_name',
                'origin.name as origin_city_name',
                'destination.name as destination_city_name',
                'routes.updated_at'
            )
            ->get();

        foreach ($routes as $route) {
            $urls[] = [
                'loc' => route('search', [
                    'origin' => $route->origin_city_name,
                    'destination' => $route->destination_city_name,
                ]),
                'priority' => '0.7',
                'changefreq' => 'daily',
                'lastmod' => $route->updated_at ? \Carbon\Carbon::parse($route->updated_at)->toIso8601String() : now()->toIso8601String(),
            ];
        }

        // 4. Rental Vehicle Details
        $rentalVehicles = VehicleRentalSetting::with(['vehicle.agency'])
            ->where('is_available_for_rental', true)
            ->whereHas('vehicle', fn($q) => $q->where('is_active', true))
            ->whereHas('vehicle.agency', fn($q) => $q->where('is_verified', true))
            ->select('id', 'updated_at')
            ->get();

        foreach ($rentalVehicles as $setting) {
            $urls[] = [
                'loc' => route('rental.public.show', $setting),
                'priority' => '0.7',
                'changefreq' => 'daily',
                'lastmod' => $setting->updated_at->toIso8601String(),
            ];
        }

        // 5. Promo Pages (jika ada halaman detail promo)
        $promos = Promo::active()
            ->select('id', 'name', 'updated_at')
            ->get();

        foreach ($promos as $promo) {
            $urls[] = [
                'loc' => route('search') . '?promo=' . $promo->id,
                'priority' => '0.6',
                'changefreq' => 'weekly',
                'lastmod' => $promo->updated_at->toIso8601String(),
            ];
        }

        return $urls;
    }

    /**
     * Build XML dari array URLs
     */
    private function buildSitemapXml(array $urls): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>');

        foreach ($urls as $urlData) {
            $url = $xml->addChild('url');
            $url->addChild('loc', $urlData['loc']);
            $url->addChild('priority', $urlData['priority'] ?? '0.5');
            $url->addChild('changefreq', $urlData['changefreq'] ?? 'weekly');

            if (isset($urlData['lastmod'])) {
                $url->addChild('lastmod', $urlData['lastmod']);
            }
        }

        // Format XML dengan indentation
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        return $dom->saveXML();
    }

    /**
     * Generate sitemap index (untuk sitemap besar)
     */
    public function generateIndex(): string
    {
        $sitemaps = [
            ['loc' => url('/sitemap-pages.xml'), 'lastmod' => now()->toIso8601String()],
            ['loc' => url('/sitemap-agencies.xml'), 'lastmod' => now()->toIso8601String()],
            ['loc' => url('/sitemap-routes.xml'), 'lastmod' => now()->toIso8601String()],
            ['loc' => url('/sitemap-rentals.xml'), 'lastmod' => now()->toIso8601String()],
        ];

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>');

        foreach ($sitemaps as $sitemap) {
            $sitemapEl = $xml->addChild('sitemap');
            $sitemapEl->addChild('loc', $sitemap['loc']);
            $sitemapEl->addChild('lastmod', $sitemap['lastmod']);
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        return $dom->saveXML();
    }
}