<?php

namespace Aerni\AdvancedSeo\Commands;

use Closure;
use Statamic\Statamic;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Illuminate\Support\Facades\Facade;
use Aerni\AdvancedSeo\Stache\SeoDefaultsRepository;
use Aerni\AdvancedSeo\Contracts\SeoDefaultsRepository as SeoDefaultsRepositoryContract;
use Aerni\AdvancedSeo\Eloquent\SeoDefaultModel;
use Aerni\AdvancedSeo\Eloquent\SeoDefaultSet;

class ExportAdvancedSeoDefaultsToFiles extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:export-advanced-seo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export eloquent Advanced SEO defaults to file based.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->usingDefaultRepositories(fn () => $this->exportAdvancedSeoDefaults());

        return 0;
    }

    private function usingDefaultRepositories(Closure $callback)
    {
        Facade::clearResolvedInstance(SeoDefaultsRepositoryContract::class);

        Statamic::repository(SeoDefaultsRepositoryContract::class, SeoDefaultsRepository::class);

        $callback();
    }

    private function exportAdvancedSeoDefaults()
    {
        $this->withProgressBar(SeoDefaultModel::all(), function ($model) {
            SeoDefaultSet::fromModel($model)->save();
        });

        $this->newLine();
        $this->info('Advanced SEO defaults exported');
    }
}
