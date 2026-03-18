<?php

namespace Aerni\AdvancedSeo\Commands;

use Aerni\AdvancedSeo\Contracts\SeoSetConfig as SeoSetConfigContract;
use Aerni\AdvancedSeo\Contracts\SeoSetConfigRepository as SeoSetConfigRepositoryContract;
use Aerni\AdvancedSeo\Contracts\SeoSetLocalization as SeoSetLocalizationContract;
use Aerni\AdvancedSeo\Contracts\SeoSetLocalizationRepository as SeoSetLocalizationRepositoryContract;
use Aerni\AdvancedSeo\Eloquent\SeoSetConfig as EloquentSeoSetConfig;
use Aerni\AdvancedSeo\Eloquent\SeoSetConfigModel;
use Aerni\AdvancedSeo\Eloquent\SeoSetLocalization as EloquentSeoSetLocalization;
use Aerni\AdvancedSeo\Eloquent\SeoSetLocalizationModel;
use Aerni\AdvancedSeo\Facades\SeoConfig;
use Aerni\AdvancedSeo\Facades\SeoLocalization;
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
use function Laravel\Prompts\progress;

class SwitchToEloquent extends Command
{
    use RunsInPlease;

    protected $signature = 'seo:switch-to-eloquent';

    protected $description = 'Switch from flat-files to Eloquent';

    public function handle()
    {
        if (! Composer::isInstalled('statamic/eloquent-driver')) {
            return error('The Eloquent driver is not installed. Run `composer require statamic/eloquent-driver` first.');
        }

        if ($this->isUsingEloquentDriver()) {
            return info('Already using the Eloquent driver. No changes needed.');
        }

        $this->switchToEloquentDriver();
        $this->runMigrations();
        $this->migrateContent();
    }

    protected function isUsingEloquentDriver(): bool
    {
        $configPath = config_path('advanced-seo.php');

        if (! file_exists($configPath)) {
            return false;
        }

        return preg_match("/('driver'\s*=>\s*)'eloquent'/", file_get_contents($configPath));
    }

    protected function switchToEloquentDriver(): void
    {
        $configPath = config_path('advanced-seo.php');

        if (! file_exists($configPath)) {
            $this->call('vendor:publish', [
                '--tag' => 'advanced-seo-config',
            ]);
        }

        $config = file_get_contents($configPath);

        if (preg_match("/('driver'\s*=>\s*)'[^']*'/", $config)) {
            $config = preg_replace("/('driver'\s*=>\s*)'[^']*'/", "\${1}'eloquent'", $config, 1);
        } else {
            preg_match(
                '/(\s*\/\*[\s\S]*?\*\/\s*\'driver\'\s*=>\s*)[\'"][^\'"]*[\'"],?/',
                file_get_contents(__DIR__.'/../../config/advanced-seo.php'),
                $matches
            );
            $config = preg_replace("/return\s*\[\s*/", 'return ['.$matches[1]."'eloquent',\n\n    ", $config, 1);
        }

        file_put_contents($configPath, $config);

        info('Switched config to the Eloquent driver.');
    }

    protected function runMigrations(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'advanced-seo-migrations',
        ]);

        $this->call('migrate');

        info('Published and ran database migrations.');
    }

    protected function migrateContent(): void
    {
        if (! confirm('Do you want to import existing data into the database?')) {
            return;
        }

        Facade::clearResolvedInstance(SeoSetConfigRepositoryContract::class);
        Facade::clearResolvedInstance(SeoSetLocalizationRepositoryContract::class);

        Statamic::repository(SeoSetConfigRepositoryContract::class, StacheSeoSetConfigRepository::class);
        Statamic::repository(SeoSetLocalizationRepositoryContract::class, StacheSeoSetLocalizationRepository::class);

        app()->bind(SeoSetConfigContract::class, EloquentSeoSetConfig::class);
        app()->bind(SeoSetLocalizationContract::class, EloquentSeoSetLocalization::class);
        app()->bind('statamic.eloquent.seo_set_config.model', SeoSetConfigModel::class);
        app()->bind('statamic.eloquent.seo_set_localization.model', SeoSetLocalizationModel::class);

        $this->importConfigs();
        $this->importLocalizations();

        info('Successfully switched to the Eloquent driver.');
    }

    protected function importConfigs(): void
    {
        progress(
            label: 'Importing configs...',
            steps: SeoConfig::all(),
            callback: fn ($config) => EloquentSeoSetConfig::makeModelFromContract($config)->save(),
        );

        info('Imported configs.');
    }

    protected function importLocalizations(): void
    {
        progress(
            label: 'Importing localizations...',
            steps: SeoLocalization::all(),
            callback: fn ($localization) => EloquentSeoSetLocalization::makeModelFromContract($localization)->save(),
        );

        info('Imported localizations.');
    }
}
