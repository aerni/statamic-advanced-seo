<?php

namespace Aerni\AdvancedSeo\Commands;

use Statamic\Statamic;
use Illuminate\Console\Command;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Console\RunsInPlease;
use Illuminate\Support\Facades\Facade;
use Aerni\AdvancedSeo\Eloquent\SeoDefaultSet as EloquentSeoDefaultSet;
use Aerni\AdvancedSeo\Stache\SeoDefaultsRepository;
use Aerni\AdvancedSeo\Contracts\SeoDefaultSet as SeoDefaultSetContract;
use Aerni\AdvancedSeo\Contracts\SeoDefaultsRepository as SeoDefaultsRepositoryContract;

class ImportAdvancedSeoDefaultsToDatabase extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:import-advanced-seo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports file-based Advanced SEO defaults into the database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->useDefaultRepositories();

        $this->importAdvancedSeoDefaults();

        return 0;
    }

    private function useDefaultRepositories(): void
    {
        Facade::clearResolvedInstance(SeoDefaultsRepositoryContract::class);

        Statamic::repository(SeoDefaultsRepositoryContract::class, SeoDefaultsRepository::class);

        app()->bind(SeoDefaultSetContract::class, EloquentSeoDefaultSet::class);
    }

    private function importAdvancedSeoDefaults(): void
    {
        $this->withProgressBar(Seo::all()->flatten(), function ($set) {
            EloquentSeoDefaultSet::makeModelFromContract($set)->save();
        });

        $this->components->info('Advanced SEO defaults imported successfully.');
    }
}
