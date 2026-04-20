<?php

namespace Aerni\AdvancedSeo\Commands;

use Aerni\AdvancedSeo\AdvancedSeo;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Features\Ai;
use Aerni\AdvancedSeo\Features\EloquentDriver;
use Aerni\AdvancedSeo\Features\GraphQL;
use Aerni\AdvancedSeo\Features\Sitemap;
use Aerni\AdvancedSeo\Features\SocialImagesGenerator;
use Aerni\AdvancedSeo\Migrators\AardvarkSeoMigrator;
use Aerni\AdvancedSeo\Migrators\SeoProMigrator;
use Facades\Statamic\Console\Processes\Composer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Statamic\Console\RunsInPlease;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class Install extends Command
{
    use RunsInPlease;

    protected $signature = 'seo:install';

    protected $description = 'Install and configure Advanced SEO';

    public function handle(): void
    {
        $this
            ->publishConfig()
            ->setupPro()
            ->migrate()
            ->setupLayout();

        info('Advanced SEO has been installed successfully.');
    }

    protected function publishConfig(): self
    {
        $this->callSilently('vendor:publish', [
            '--tag' => 'advanced-seo-config',
        ]);

        return $this;
    }

    protected function setupPro(): self
    {
        if (! $this->enableProEdition()) {
            return $this;
        }

        foreach ($this->askProFeatures() as $feature) {
            match ($feature) {
                'sitemap' => $this->setupSitemap(),
                'ai' => $this->setupAi(),
                'social_images' => $this->setupSocialImages(),
                'graphql' => $this->setupGraphQl(),
                'eloquent' => $this->setupEloquent(),
            };
        }

        return $this;
    }

    protected function setupLayout(): self
    {
        $layout = config('statamic.system.layout', 'layout');

        $antlersLayout = resource_path("views/{$layout}.antlers.html");
        $bladeLayout = resource_path("views/{$layout}.blade.php");

        match (true) {
            File::exists($antlersLayout) => $this->injectIntoLayout($antlersLayout),
            File::exists($bladeLayout) => $this->injectIntoLayout($bladeLayout),
            default => warning('Could not find a layout file. Please add the SEO tags manually.'),
        };

        return $this;
    }

    protected function enableProEdition(): bool
    {
        if (AdvancedSeo::edition() === 'pro') {
            return true;
        }

        $usePro = confirm(
            label: 'Would you like to use Pro?',
            default: false,
            hint: 'Includes multi-site, permissions, sitemaps, AI content generation, and more.',
        );

        if (! $usePro) {
            return false;
        }

        // Set the edition at runtime so Feature::enabled() checks work
        config(['statamic.editions.addons.aerni/advanced-seo' => 'pro']);

        $configPath = config_path('statamic/editions.php');
        $contents = File::get($configPath);

        if (Str::contains($contents, "'aerni/advanced-seo'")) {
            $contents = preg_replace(
                "/'aerni\/advanced-seo'\s*=>\s*'[^']*'/",
                "'aerni/advanced-seo' => 'pro'",
                $contents,
            );
        } else {
            $contents = preg_replace(
                "/'addons'\s*=>\s*\[\s*(?:\/\/\s*)?\n/",
                "'addons' => [\n        'aerni/advanced-seo' => 'pro',\n",
                $contents,
                1,
            );
        }

        File::put($configPath, $contents);

        return true;
    }

    /**
     * @return array<int, string>
     */
    protected function askProFeatures(): array
    {
        $features = collect([
            [
                'key' => 'sitemap',
                'label' => 'Sitemaps',
                'enabled' => Sitemap::enabled(),
            ],
            [
                'key' => 'ai',
                'label' => 'AI Copywriting',
                'enabled' => Ai::enabled(),
            ],
            [
                'key' => 'social_images',
                'label' => 'Social Images Generator',
                'enabled' => SocialImagesGenerator::enabled(),
            ],
            [
                'key' => 'eloquent',
                'label' => 'Eloquent Driver',
                'enabled' => EloquentDriver::enabled(),
            ],
            [
                'key' => 'graphql',
                'label' => 'GraphQL API',
                'enabled' => GraphQL::enabled(),
            ],
        ])->reject(fn ($feature) => $feature['enabled']);

        if ($features->isEmpty()) {
            return [];
        }

        return collect(multiselect(
            label: 'Select the Pro features you would like to set up.',
            options: $features->pluck('label', 'key'),
        ))->sortBy(fn ($key) => $features->pluck('key')->search($key))->values()->all();
    }

    protected function setupSitemap(): void
    {
        $this->setConfigValue('sitemap.enabled', 'true');
        info('Sitemaps enabled.');
    }

    protected function setupAi(): void
    {
        note('Setting up AI Copywriting...');
        $this->setConfigValue('ai.enabled', 'true');

        if (! Composer::isInstalled('laravel/ai')) {
            spin(
                callback: fn () => Composer::withoutQueue()->throwOnFailure()->require('laravel/ai'),
                message: 'Installing laravel/ai...',
            );

            config(['ai' => require base_path('vendor/laravel/ai/config/ai.php')]);
        }

        $providers = collect(config('ai.providers'))->keys();
        $default = config('ai.default');

        $options = collect(['' => "Default ({$default})"])
            ->merge($providers->mapWithKeys(fn ($provider) => [$provider => Str::title($provider)]))
            ->all();

        $provider = select(
            label: 'Which AI provider would you like to use?',
            options: $options,
            default: '',
            info: function (string $value) use ($default) {
                $provider = $value !== '' ? $value : $default;

                return empty(config("ai.providers.{$provider}.key"))
                    ? "No API key configured for {$provider}."
                    : '';
            },
        );

        $this->setConfigValue('ai.provider', $provider !== '' ? "'{$provider}'" : 'null');

        $model = text(
            label: 'Which AI model would you like to use?',
            hint: 'Leave empty to use the default model.',
        );

        $this->setConfigValue('ai.model', $model !== '' ? "'{$model}'" : 'null');
        info('AI Copywriting configured.');
    }

    protected function setupSocialImages(): void
    {
        note('Setting up Social Images Generator...');
        $this->setConfigValue('social_images.generator.enabled', 'true');

        if (! Composer::isInstalled('spatie/laravel-screenshot')) {
            spin(
                callback: fn () => Composer::withoutQueue()->throwOnFailure()->require('spatie/laravel-screenshot'),
                message: 'Installing spatie/laravel-screenshot...',
            );
        }

        $screenshotDriver = select(
            label: 'Which screenshot driver would you like to use?',
            options: [
                'browsershot' => 'Browsershot',
                'cloudflare' => 'Cloudflare Browser Rendering',
            ],
            default: 'browsershot',
        );

        $this->addEnvironmentVariables(['LARAVEL_SCREENSHOT_DRIVER' => $screenshotDriver]);

        if ($screenshotDriver === 'browsershot' && ! Composer::isInstalled('spatie/browsershot')) {
            spin(
                callback: fn () => Composer::withoutQueue()->throwOnFailure()->require('spatie/browsershot'),
                message: 'Installing spatie/browsershot...',
            );
        }

        if ($screenshotDriver === 'cloudflare') {
            if (! $this->envHas('CLOUDFLARE_API_TOKEN')) {
                $this->addEnvironmentVariables(['CLOUDFLARE_API_TOKEN' => text(
                    label: 'Cloudflare API Token',
                    hint: 'Leave empty to configure later in your .env file.',
                )]);
            }

            if (! $this->envHas('CLOUDFLARE_ACCOUNT_ID')) {
                $this->addEnvironmentVariables(['CLOUDFLARE_ACCOUNT_ID' => text(
                    label: 'Cloudflare Account ID',
                    hint: 'Leave empty to configure later in your .env file.',
                )]);
            }
        }

        if (SocialImage::themes()->all()->isEmpty()) {
            $this->callSilently('seo:theme', ['name' => 'default']);
        }

        info('Social Images Generator configured.');
    }

    protected function setupGraphQl(): void
    {
        $this->setConfigValue('graphql', 'true');
        info('GraphQL API enabled.');
    }

    protected function setupEloquent(): void
    {
        note('Setting up Eloquent Driver...');

        if (! Composer::isInstalled('statamic/eloquent-driver')) {
            spin(
                callback: fn () => Composer::withoutQueue()->throwOnFailure()->require('statamic/eloquent-driver'),
                message: 'Installing statamic/eloquent-driver...',
            );
        }

        $success = spin(
            callback: fn () => $this->runArtisanCommand('seo:switch-to-eloquent --no-interaction'),
            message: 'Switching to Eloquent driver...',
        );

        $success
            ? info('Eloquent Driver configured.')
            : warning('Failed to switch to Eloquent driver. Run `php artisan seo:switch-to-eloquent` manually.');
    }

    protected function migrate(): self
    {
        $migrator = select(
            label: 'Select an addon to migrate from.',
            options: [
                'none' => 'None',
                AardvarkSeoMigrator::class => 'Aardvark SEO',
                SeoProMigrator::class => 'SEO Pro',
            ],
            default: 'none',
        );

        if ($migrator !== 'none') {
            spin(
                callback: fn () => resolve($migrator)::run(),
                message: 'Migrating data...',
            );

            info('The migration has been completed successfully.');
        }

        return $this;
    }

    protected function injectIntoLayout(string $path): void
    {
        $content = File::get($path);
        $isAntlers = str_ends_with($path, '.antlers.html');

        $headTag = $isAntlers ? '{{ seo:head }}' : "@seo('head')";
        $bodyTag = $isAntlers ? '{{ seo:body }}' : "@seo('body')";

        $original = $content;

        if (! str_contains($content, $headTag)) {
            $content = preg_replace_callback(
                '/^(\s*)<\/head>/mi',
                fn ($matches) => $matches[1].'    '.$headTag."\n".$matches[0],
                $content,
                1
            );
        }

        if (! str_contains($content, $bodyTag)) {
            $content = preg_replace_callback(
                '/^(\s*)<body([^>]*)>/mi',
                fn ($matches) => $matches[0]."\n".$matches[1].'    '.$bodyTag,
                $content,
                1
            );
        }

        if ($content !== $original) {
            File::put($path, $content);

            $relativePath = Str::after($path, base_path('/'));
            info("Added SEO tags to {$relativePath}.");
            note("Review your layout's <head> and remove any existing SEO tags (title, description, Open Graph, etc.) that would conflict with {$headTag}.");
        }
    }

    protected function runArtisanCommand(string $command): bool
    {
        return Process::forever()->run(
            [PHP_BINARY, defined('ARTISAN_BINARY') ? ARTISAN_BINARY : 'artisan', ...explode(' ', $command)],
        )->successful();
    }

    protected function envHas(string $key): bool
    {
        return (bool) preg_match("/^{$key}=.+/m", File::get(base_path('.env')));
    }

    protected function setConfigValue(string $key, string $value): void
    {
        $configPath = config_path('advanced-seo.php');
        $config = File::get($configPath);

        $segments = collect(explode('.', $key))
            ->map(fn ($segment) => "'{$segment}'")
            ->join('.*?');

        $pattern = "/({$segments}\s*=>\s*)(null|true|false|'[^']*')/s";
        $config = preg_replace($pattern, "\${1}{$value}", $config, 1);

        File::put($configPath, $config);
    }

    /**
     * @param  array<string, string>  $variables
     */
    protected function addEnvironmentVariables(array $variables): void
    {
        $env = base_path('.env');
        $contents = File::get($env);
        $newLines = [];

        foreach ($variables as $key => $value) {
            if (Str::contains($contents, PHP_EOL.$key.'=')) {
                $contents = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $contents);
            } else {
                $newLines[] = "{$key}={$value}";
            }
        }

        if ($newLines) {
            $block = implode(PHP_EOL, $newLines);
            $contents = Str::endsWith($contents, PHP_EOL)
                ? $contents.PHP_EOL.$block.PHP_EOL
                : $contents.PHP_EOL.PHP_EOL.$block.PHP_EOL;
        }

        File::put($env, $contents);
    }
}
