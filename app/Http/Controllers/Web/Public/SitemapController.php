<?php

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Controller;
use App\Services\SitemapService;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(
        private readonly SitemapService $sitemapService,
    ) {}

    public function index(): Response
    {
        $content = $this->sitemapService->generate();

        return response($content, 200)
            ->header('Content-Type', 'application/xml')
            ->header('X-Robots-Tag', 'noindex, follow');
    }

    public function save(): Response
    {
        $this->sitemapService->generateAndSave();

        return response()->json([
            'success' => true,
            'message' => 'Sitemap berhasil di-generate dan disimpan.',
        ]);
    }

    public function clear(): Response
    {
        $this->sitemapService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Cache sitemap berhasil dibersihkan.',
        ]);
    }
}