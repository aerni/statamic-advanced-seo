<?php

namespace Aerni\AdvancedSeo\Commands;

use Aerni\AdvancedSeo\Contracts\SeoSetConfigRepository as SeoSetConfigRepositoryContract;
use Aerni\AdvancedSeo\Contracts\SeoSetLocalizationRepository as SeoSetLocalizationRepositoryContract;
use Aerni\AdvancedSeo\Eloquent\SeoSetConfig as EloquentSeoSetConfig;
use Aerni\AdvancedSeo\Eloquent\SeoSetConfigModel;
use Aerni\AdvancedSeo\Eloquent\SeoSetLocalization as EloquentSeoSetLocalization;
use Aerni\AdvancedSeo\Eloquent\SeoSetLocalizationModel;
use Aerni\AdvancedSeo\Stache\Repositories\SeoSetConfigRepository as StacheSeoSetConfigRepository;
use Aerni\AdvancedSeo\Stache\Repositories\SeoSetLocalizationRepository as StacheSeoSetLocalizationRepository;
use Facades\Statamic\Console\Processes\Composer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Facade;
use Statamic\Console\RunsInPlease;
use Statamic\Statamic;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class SwitchToFile extends Command
{
    use RunsInPlease;

    protected $signature = 'seo:switch-to-file';

    protected $description = 'Switch from Eloquent to flat-files.';

    public function handle()
    {
        if (! Composer::isInstalled('statamic/eloquent-driver')) {
            return error('You need to install the Eloquent Driver before running this command. Run `composer require statamic/eloquent-driver`.');
        }

        $this->switchToFileDriver();
        $this->migrateContent();
    }

    protected function switchToFileDriver(): void
    {
        $configPath = config_path('advanced-seo.php');

        if (file_exists($configPath) && preg_match("/('driver'\\s*=>\\s*)'file'/", file_get_contents($configPath))) {
            return;
        }

        $this->call('vendor:publish', [
            '--tag' => 'advanced-seo-config',
        ]);

        $config = preg_replace("/('driver'\s*=>\s*)'[^']*'/", "\${1}'file'", file_get_contents($configPath), 1);

        file_put_contents($configPath, $config);

        info('Updated config to use the File driver.');
    }

    protected function migrateContent(): void
    {
        if (! confirm('Do you want to export existing data to flat-files?')) {
            return;
        }

        Facade::clearResolvedInstance(SeoSetConfigRepositoryContract::class);
        Facade::clearResolvedInstance(SeoSetLocalizationRepositoryContract::class);

        Statamic::repository(SeoSetConfigRepositoryContract::class, StacheSeoSetConfigRepository::class);
        Statamic::repository(SeoSetLocalizationRepositoryContract::class, StacheSeoSetLocalizationRepository::class);

        $this->exportConfigs();
        $this->exportLocalizations();

        info('Advanced SEO is now using flat-files to store its data.');
    }

    protected function exportConfigs(): void
    {
        $this->withProgressBar(SeoSetConfigModel::all(), function ($model) {
            EloquentSeoSetConfig::fromModel($model)->save();
        });

        $this->newline();

        info('Configs exported successfully');
    }

    protected function exportLocalizations(): void
    {
        $this->withProgressBar(SeoSetLocalizationModel::all(), function ($model) {
            EloquentSeoSetLocalization::fromModel($model)->save();
        });

        $this->newline();

        info('Localizations exported successfully');
    }
}
