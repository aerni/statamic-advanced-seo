<?php

namespace Aerni\AdvancedSeo\Jobs;

use Aerni\AdvancedSeo\Facades\Sitemap;
use Aerni\AdvancedSeo\Features\Sitemap as Feature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class GenerateSitemapsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(public string $site)
    {
        $this->queue = config('advanced-seo.sitemap.queue', 'default');
    }

    public function handle(): void
    {
        if (! Feature::enabled()) {
            return;
        }

        Sitemap::generate($this->site);
    }
}
