<?php

namespace Aerni\AdvancedSeo\Commands;

use Aerni\AdvancedSeo\Facades\Sitemap;
use Aerni\AdvancedSeo\Jobs\GenerateSitemapsJob;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\warning;

class GenerateSitemaps extends Command
{
    use RunsInPlease;

    protected $signature = 'seo:generate-sitemaps {--queue}';

    protected $description = 'Generate the sitemaps';

    protected bool $shouldQueue = false;

    public function handle()
    {
        if (! config('advanced-seo.sitemap.enabled')) {
            return error('The sitemap feature is disabled. You need to enable it to generate the sitemaps.');
        }

        if (! in_array(app()->environment(), config('advanced-seo.crawling.environments', []))) {
            return error('The current environment is protected from being crawled. To generate the sitemaps, you need to add this environment to the crawling config.');
        }

        $this->shouldQueue = $this->option('queue');

        if ($this->shouldQueue && config('queue.default') === 'sync') {
            warning('The queue connection is set to "sync". Queueing will be disabled.');
            $this->shouldQueue = false;
        }

        $sitemaps = collect([Sitemap::index()])->merge(Sitemap::all());

        $this->shouldQueue
            ? GenerateSitemapsJob::dispatch($sitemaps)
            : spin(fn () => GenerateSitemapsJob::dispatchSync($sitemaps), 'Generating sitemaps ...');

        $this->shouldQueue
            ? info('All requests to generate the sitemaps have been added to the queue.')
            : info('The sitemaps have been succesfully generated.');
    }
}
