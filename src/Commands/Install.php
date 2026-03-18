<?php

namespace Aerni\AdvancedSeo\Commands;

use Aerni\AdvancedSeo\Facades\SocialImageTheme;
use Aerni\AdvancedSeo\Features\Ai;
use Aerni\AdvancedSeo\Features\SocialImagesGenerator;
use Aerni\AdvancedSeo\Migrators\AardvarkSeoMigrator;
use Aerni\AdvancedSeo\Migrators\SeoProMigrator;
use Facades\Statamic\Console\Processes\Composer;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;
use Statamic\Console\RunsInPlease;

class Install extends Command
{
    use RunsInPlease;

    protected $signature = 'seo:install';

    protected $description = 'Install and configure Advanced SEO';

    protected array $selectedFeatures = [];

    protected ?string $screenshotDriver = null;

    protected array $cloudflareVariables = [];

    protected ?string $migrator = null;

    public function handle(): void
    {
        $this
            ->askFeatures()
            ->askMigration()
            ->publishConfig()
            ->setupLayout()
            ->setupFeatures()
            ->runMigration();

        info('Advanced SEO has been installed successfully.');
    }

    protected function askFeatures(): self
    {
        $features = collect([
            [
                'key' => 'ai',
                'label' => 'AI Content Generation',
                'enabled' => Ai::enabled(),
            ],
            [
                'key' => 'social_images',
                'label' => 'Social Images Generator',
                'enabled' => SocialImagesGenerator::enabled(),
            ],
            [
                'key' => 'graphql',
                'label' => 'GraphQL API',
                'enabled' => config('advanced-seo.graphql', false),
            ],
        ])->reject(fn ($feature) => $feature['enabled']);

        if ($features->isNotEmpty()) {
            $this->selectedFeatures = multiselect(
                label: 'Select the features you would like to enable.',
                options: $features->pluck('label', 'key'),
            );
        }

        if (in_array('social_images', $this->selectedFeatures)) {
            $this->askScreenshotDriver();
        }

        return $this;
    }

    protected function askScreenshotDriver(): void
    {
        $this->screenshotDriver = select(
            label: 'Which screenshot driver would you like to use?',
            options: [
                'browsershot' => 'Browsershot',
                'cloudflare' => 'Cloudflare Browser Rendering',
            ],
            default: 'browsershot',
        );

        if ($this->screenshotDriver === 'browsershot') {
            return;
        }

        $this->cloudflareVariables['LARAVEL_SCREENSHOT_DRIVER'] = 'cloudflare';

        if (! $this->envHas('CLOUDFLARE_API_TOKEN')) {
            $this->cloudflareVariables['CLOUDFLARE_API_TOKEN'] = text(
                label: 'Cloudflare API Token',
                hint: 'Leave empty to configure later in your .env file.',
            );
        }

        if (! $this->envHas('CLOUDFLARE_ACCOUNT_ID')) {
            $this->cloudflareVariables['CLOUDFLARE_ACCOUNT_ID'] = text(
                label: 'Cloudflare Account ID',
                hint: 'Leave empty to configure later in your .env file.',
            );
        }
    }

    protected function askMigration(): self
    {
        $migrator = select(
            label: 'Do you want to migrate from another SEO addon?',
            options: [
                'none' => 'No',
                AardvarkSeoMigrator::class => 'Aardvark SEO',
                SeoProMigrator::class => 'SEO Pro',
            ],
            default: 'none',
        );

        if ($migrator !== 'none') {
            $this->migrator = $migrator;
        }

        return $this;
    }

    protected function publishConfig(): self
    {
        $this->callSilently('vendor:publish', [
            '--tag' => 'advanced-seo-config',
        ]);

        return $this;
    }

    protected function setupLayout(): self
    {
        $layout = config('statamic.system.layout', 'layout');

        $antlersLayout = resource_path("views/{$layout}.antlers.html");
        $bladeLayout = resource_path("views/{$layout}.blade.php");

        match (true) {
            file_exists($antlersLayout) => $this->injectIntoLayout($antlersLayout),
            file_exists($bladeLayout) => $this->injectIntoLayout($bladeLayout),
            default => warning('Could not find a layout file. Please add the SEO tags manually.'),
        };

        return $this;
    }

    protected function setupFeatures(): self
    {
        foreach ($this->selectedFeatures as $feature) {
            match ($feature) {
                'ai' => $this->setupAi(),
                'social_images' => $this->setupSocialImages(),
                'graphql' => $this->setupGraphQl(),
            };
        }

        return $this;
    }

    protected function setupAi(): void
    {
        $this->enableConfigValue('ai');

        if (! Composer::isInstalled('laravel/ai')) {
            spin(
                callback: fn () => Composer::withoutQueue()->throwOnFailure()->require('laravel/ai'),
                message: 'Installing laravel/ai...',
            );
        }

        info('AI Content Generation has been set up.');
        note("To use AI Content Generation, you need to configure a provider in config/ai.php.\nYou can optionally override the provider and model in config/advanced-seo.php.");
    }

    protected function setupSocialImages(): void
    {
        $this->enableConfigValue('social_images');

        if (! Composer::isInstalled('spatie/laravel-screenshot')) {
            spin(
                callback: fn () => Composer::withoutQueue()->throwOnFailure()->require('spatie/laravel-screenshot'),
                message: 'Installing spatie/laravel-screenshot...',
            );
        }

        if ($this->screenshotDriver === 'browsershot' && ! Composer::isInstalled('spatie/browsershot')) {
            spin(
                callback: fn () => Composer::withoutQueue()->throwOnFailure()->require('spatie/browsershot'),
                message: 'Installing spatie/browsershot...',
            );
        }

        if ($this->screenshotDriver === 'cloudflare') {
            $this->addEnvironmentVariables($this->cloudflareVariables);
        }

        if (SocialImageTheme::all()->isEmpty()) {
            $this->callSilently('seo:theme', ['name' => 'default']);
        }

        info('Social Images Generator has been set up.');
    }

    protected function setupGraphQl(): void
    {
        $this->enableConfigValue('graphql');
        info('GraphQL API has been enabled.');
    }

    protected function runMigration(): self
    {
        if (! $this->migrator) {
            return $this;
        }

        spin(
            callback: fn () => resolve($this->migrator)::run(),
            message: 'Migrating data...',
        );

        info('The migration has been completed successfully.');

        return $this;
    }

    protected function injectIntoLayout(string $path): void
    {
        $content = file_get_contents($path);
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
            file_put_contents($path, $content);

            $relativePath = Str::after($path, base_path('/'));
            info("Added SEO tags to {$relativePath}.");
        }
    }

    protected function envHas(string $key): bool
    {
        return Str::contains(file_get_contents(base_path('.env')), PHP_EOL.$key.'=');
    }

    protected function enableConfigValue(string $key): void
    {
        $configPath = config_path('advanced-seo.php');
        $config = file_get_contents($configPath);

        $patterns = [
            'social_images' => "/('social_images'.*?'generator'.*?'enabled'\s*=>\s*)false/s",
            'ai' => "/('ai'.*?'enabled'\s*=>\s*)false/s",
            'graphql' => "/('graphql'\s*=>\s*)false/",
        ];

        if (isset($patterns[$key])) {
            $config = preg_replace($patterns[$key], '${1}true', $config, 1);
            file_put_contents($configPath, $config);
        }
    }

    /**
     * @param  array<string, string>  $variables
     */
    protected function addEnvironmentVariables(array $variables): void
    {
        $env = base_path('.env');
        $contents = file_get_contents($env);
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

        file_put_contents($env, $contents);
    }
}
