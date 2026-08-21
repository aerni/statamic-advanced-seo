<?php

namespace Aerni\AdvancedSeo\UpdateScripts;

use Facades\Statamic\Console\Processes\Composer;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Statamic\UpdateScripts\UpdateScript;

class AddRedirects extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion): bool
    {
        return $this->isUpdatingTo('3.3.0');
    }

    public function update(): void
    {
        $this->addRedirectsConfig();

        if ($this->usesEloquentDriver()) {
            $this->migrateEloquentTables();
        }
    }

    protected function addRedirectsConfig(): void
    {
        $configPath = config_path('advanced-seo.php');

        if (! File::exists($configPath)) {
            return;
        }

        $contents = File::get($configPath);

        if (preg_match('/[\'\"]redirects[\'\"]\s*=>/', $contents)) {
            return;
        }

        $contents = Str::of($contents)
            ->rtrim()
            ->beforeLast('];')
            ->rtrim()
            ->append("\n\n", File::get(__DIR__.'/stubs/redirects_config.php.stub'), "\n\n];\n");

        File::put($configPath, $contents);
    }

    protected function usesEloquentDriver(): bool
    {
        return Composer::isInstalled('statamic/eloquent-driver')
            && config('advanced-seo.driver') === 'eloquent';
    }

    protected function migrateEloquentTables(): void
    {
        Artisan::call('vendor:publish', ['--tag' => 'advanced-seo-migrations']);
        Artisan::call('migrate');
    }
}
