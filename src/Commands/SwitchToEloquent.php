<?php

namespace Aerni\AdvancedSeo\Commands;

use Aerni\AdvancedSeo\Contracts\SeoDefaultSet as SeoDefaultSetContract;
use Aerni\AdvancedSeo\Contracts\SeoDefaultsRepository as SeoDefaultsRepositoryContract;
use Aerni\AdvancedSeo\Eloquent\SeoDefaultModel;
use Aerni\AdvancedSeo\Eloquent\SeoDefaultSet as EloquentSeoDefaultSet;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Stache\SeoDefaultsRepository as StacheSeoDefaultsRepository;
use Facades\Statamic\Console\Processes\Composer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Facade;
use Statamic\Console\RunsInPlease;
use Statamic\Statamic;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class SwitchToEloquent extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:switch-to-eloquent';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Switch from flat-files to using Eloquent.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! Composer::isInstalled('statamic/eloquent-driver')) {
            return error('You need to install the Eloquent Driver before running this command. Run `composer require statamic/eloquent-driver`.');
        }

        $this
            ->switchToEloquentDriver()
            ->migrateContent();

        info('Advanced SEO is now using Eloquent to store its data.');
    }

    protected function switchToEloquentDriver(): self
    {
        $this->callSilently('vendor:publish', [
            '--tag' => 'advanced-seo-config',
        ]);

        $config = file_get_contents(config_path('advanced-seo.php'));

        if (preg_match("/('driver'\s*=>\s*)'[^']*'/", $config)) {
            $config = preg_replace("/('driver'\s*=>\s*)'[^']*'/", "\${1}'eloquent'", $config, 1);
        } else {
            $driver = <<<'EOD'
                /*
                |--------------------------------------------------------------------------
                | Database Driver
                |--------------------------------------------------------------------------
                |
                | Choose the driver for storing data. This can either be 'file' or 'eloquent'.
                |
                */

                'driver' => 'eloquent',
            EOD;

            $config = preg_replace("/return\s*\[/", "return [\n\n$driver", $config, 1);
        }

        file_put_contents(config_path('advanced-seo.php'), $config);

        info('Updated config to use the Eloquent driver.');

        return $this;
    }

    protected function migrateContent(): self
    {
        $this->callSilently('vendor:publish', [
            '--tag' => 'advanced-seo-migrations',
        ]);

        $this->callSilently('migrate', [
            '--path' => str_replace(base_path(), '', database_path('migrations/2025_02_05_100000_create_advanced_seo_defaults_table.php')),
        ]);

        info('Published and migrated the Advanced SEO migrations.');

        if (! confirm('Do you want to import existing data from flat-files to the database?')) {
            return $this;
        }

        Facade::clearResolvedInstance(SeoDefaultsRepositoryContract::class);

        Statamic::repository(SeoDefaultsRepositoryContract::class, StacheSeoDefaultsRepository::class);

        app()->bind(SeoDefaultSetContract::class, EloquentSeoDefaultSet::class);

        app()->bind('advanced_seo.model', SeoDefaultModel::class);

        $this->withProgressBar(Seo::all()->flatten(), function ($set) {
            EloquentSeoDefaultSet::makeModelFromContract($set)->save();
        });

        $this->newline(2);

        info('Imported data into the database.');

        return $this;
    }
}
