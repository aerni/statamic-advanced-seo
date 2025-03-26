<?php

namespace Aerni\AdvancedSeo\Commands;

use Aerni\AdvancedSeo\Contracts\SeoDefaultsRepository as SeoDefaultsRepositoryContract;
use Aerni\AdvancedSeo\Eloquent\SeoDefaultModel;
use Aerni\AdvancedSeo\Eloquent\SeoDefaultSet as EloquentSeoDefaultSet;
use Aerni\AdvancedSeo\Stache\SeoDefaultsRepository as StacheSeoDefaultsRepository;
use Facades\Statamic\Console\Processes\Composer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Facade;
use Statamic\Console\RunsInPlease;
use Statamic\Statamic;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class SwitchToStache extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:switch-to-stache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Switch from Eloquent to using flat-files.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! Composer::isInstalled('statamic/eloquent-driver')) {
            return error('You need to install the Eloquent Driver before running this command. Run `composer require statamic/eloquent-driver`.');
        }

        $this
            ->switchToStacheDriver()
            ->migrateContent();

        info('Advanced SEO is now using flat-files to store its data.');
    }

    protected function switchToStacheDriver(): self
    {
        $this->callSilently('vendor:publish', [
            '--tag' => 'advanced-seo-config',
        ]);

        $config = file_get_contents(config_path('advanced-seo.php'));

        if (preg_match("/('driver'\s*=>\s*)'[^']*'/", $config)) {
            $config = preg_replace("/('driver'\s*=>\s*)'[^']*'/", "\${1}'stache'", $config, 1);
        } else {
            $driver = <<<'EOD'
                /*
                |--------------------------------------------------------------------------
                | Database Driver
                |--------------------------------------------------------------------------
                |
                | Choose the driver for storing data. This can either be 'stache' or 'eloquent'.
                |
                */

                'driver' => 'stache',
            EOD;

            $config = preg_replace("/return\s*\[/", "return [\n\n$driver", $config, 1);
        }

        file_put_contents(config_path('advanced-seo.php'), $config);

        info('Updated config to use the stache driver.');

        return $this;
    }

    protected function migrateContent(): self
    {
        if (! confirm('Do you want to export existing Advanced SEO data to flat-files?')) {
            return $this;
        }

        Facade::clearResolvedInstance(SeoDefaultsRepositoryContract::class);

        Statamic::repository(SeoDefaultsRepositoryContract::class, StacheSeoDefaultsRepository::class);

        app()->bind('advanced_seo.model', SeoDefaultModel::class);

        $this->withProgressBar(app('advanced_seo.model')::all(), function ($model) {
            EloquentSeoDefaultSet::fromModel($model)->save();
        });

        $this->newline(2);

        info('Exported existing data to flat-files.');

        return $this;
    }
}
