<?php

namespace Aerni\AdvancedSeo\Commands;

use Aerni\AdvancedSeo\Facades\Domain;
use Aerni\AdvancedSeo\Jobs\GenerateSitemapsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Site;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\warning;

class GenerateSitemaps extends Command
{
    use RunsInPlease;

    protected $signature = 'seo:generate-sitemaps {--queue} {--site=* : Only generate sitemaps for the provided site}';

    protected $description = 'Generate all sitemaps';

    protected bool $shouldQueue = false;

    public function handle()
    {
        if (! config('advanced-seo.sitemap.enabled')) {
            return error('The sitemap feature is disabled. Enable it in config/advanced-seo.php.');
        }

        if (! in_array(app()->environment(), config('advanced-seo.crawling.environments', []))) {
            return error('The current environment is not configured for crawling. Add it to the crawling environments in config/advanced-seo.php.');
        }

        $this->shouldQueue = $this->option('queue');

        if ($this->shouldQueue && config('queue.default') === 'sync') {
            warning('The queue connection is set to "sync". Queueing will be disabled.');
            $this->shouldQueue = false;
        }

        $sites = $this->resolveSites();

        $this->shouldQueue
            ? $sites->each(fn (string $site) => GenerateSitemapsJob::dispatch($site))
            : spin(fn () => $sites->each(fn (string $site) => GenerateSitemapsJob::dispatchSync($site)), 'Generating sitemaps...');

        $this->shouldQueue
            ? info('The sitemaps have been queued for generation.')
            : info('The sitemaps have been successfully generated.');
    }

    /**
     * Resolve one representative site handle per domain to generate sitemaps for.
     */
    protected function resolveSites(): Collection
    {
        $option = collect($this->option('site'));

        $invalid = $option->diff(Site::all()->keys());

        if ($invalid->isNotEmpty()) {
            $this->fail("Can't create sitemap for invalid site: {$invalid->join(', ')}");
        }

        return Domain::all()
            ->when($option->isNotEmpty(), fn ($domains) => $domains
                ->filter(fn ($domain) => $domain->sites->contains(fn ($site) => $option->contains($site->handle()))))
            ->map(fn ($domain) => $domain->sites->first()->handle());
    }
}
