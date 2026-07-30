<?php

namespace App\Console\Commands;

use App\Services\SitemapService;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Generate sitemap.xml';

    public function handle(SitemapService $sitemapService): int
    {
        $this->info('Generating sitemap...');
        
        $sitemapService->generateAndSave();
        $sitemapService->clearCache();
        
        $this->info('Sitemap generated successfully!');
        
        return Command::SUCCESS;
    }
}