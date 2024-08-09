<?php

namespace Aerni\AdvancedSeo\Jobs;

use Aerni\AdvancedSeo\Facades\Sitemap;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class GenerateSitemapsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(protected Collection $sitemaps)
    {
        $this->queue = config('advanced-seo.sitemap.queue', 'default');
    }

    public function handle(): void
    {
        File::deleteDirectory(Sitemap::path());

        $this->sitemaps->each->save();
    }
}
