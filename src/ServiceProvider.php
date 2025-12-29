<?php

namespace Aerni\AdvancedSeo;

use Statamic\Statamic;
use Statamic\Facades\Git;
use Statamic\Facades\User;
use Illuminate\Support\Arr;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Stache;
use Statamic\Facades\GraphQL;
use Statamic\Facades\Permission;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Aerni\AdvancedSeo\Contracts\SeoSet;
use Statamic\GraphQL\Types\TermInterface;
use Statamic\GraphQL\Types\EntryInterface;
use Aerni\AdvancedSeo\View\CascadeComposer;
use Statamic\Providers\AddonServiceProvider;
use Aerni\AdvancedSeo\GraphQL\Fields\SeoField;
use Aerni\AdvancedSeo\GraphQL\Types\SeoMetaType;
use Aerni\AdvancedSeo\GraphQL\Types\SeoSetsType;
use Facades\Statamic\Console\Processes\Composer;
use Aerni\AdvancedSeo\GraphQL\Types\HreflangType;
use Aerni\AdvancedSeo\GraphQL\Queries\SeoMetaQuery;
use Aerni\AdvancedSeo\GraphQL\Queries\SeoSetsQuery;
use Aerni\AdvancedSeo\GraphQL\Types\SeoSitemapType;
use Aerni\AdvancedSeo\GraphQL\Types\RawMetaDataType;
use Aerni\AdvancedSeo\GraphQL\Types\SeoSitemapsType;
use Aerni\AdvancedSeo\GraphQL\Types\SiteDefaultsType;
use Aerni\AdvancedSeo\GraphQL\Types\RenderedViewsType;
use Aerni\AdvancedSeo\GraphQL\Queries\SeoSitemapsQuery;
use Aerni\AdvancedSeo\Stache\Stores\SeoSetConfigsStore;
use Aerni\AdvancedSeo\GraphQL\Types\ContentDefaultsType;
use Aerni\AdvancedSeo\GraphQL\Types\GeneralDefaultsType;
use Aerni\AdvancedSeo\GraphQL\Types\ComputedMetaDataType;
use Aerni\AdvancedSeo\GraphQL\Types\FaviconsDefaultsType;
use Aerni\AdvancedSeo\GraphQL\Types\IndexingDefaultsType;
use Aerni\AdvancedSeo\GraphQL\Types\AnalyticsDefaultsType;
use Aerni\AdvancedSeo\GraphQL\Types\SitemapAlternatesType;
use Aerni\AdvancedSeo\GraphQL\Types\SocialImagePresetType;
use Aerni\AdvancedSeo\GraphQL\Types\SocialMediaDefaultsType;
use Aerni\AdvancedSeo\Stache\Stores\SeoSetLocalizationsStore;

class ServiceProvider extends AddonServiceProvider
{
    protected $actions = [
        Actions\Statamic\GenerateSocialImages::class,
    ];

    protected $policies = [
        SeoSet::class => Policies\SeoConfigurationPolicy::class,
    ];

    protected $vite = [
        'input' => [
            'resources/js/cp.js',
            'resources/css/cp.css',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    public function bootAddon(): void
    {
        $this
            ->bootRouteBindings()
            ->bootStacheStore()
            ->bootNav()
            ->bootPermissions()
            ->bootGit()
            ->bootViewCascade()
            ->bootBladeDirective()
            ->bootGraphQL()
            ->bootMigrations()
            ->autoPublishConfig();
    }

    public function register(): void
    {
        $this->usesEloquentDriver()
            ? $this->registerEloquentDriver()
            : $this->registerFileDriver();
    }

    protected function usesEloquentDriver(): bool
    {
        return Composer::isInstalled('statamic/eloquent-driver')
            && config('advanced-seo.driver') === 'eloquent';
    }

    protected function registerEloquentDriver(): void
    {
        // TODO: Update Eloquent driver for new architecture
        Statamic::repository(Contracts\SeoSetConfigRepository::class, \Aerni\AdvancedSeo\Eloquent\SeoSetConfigRepository::class);

        $this->app->bind('advanced_seo.model', Eloquent\SeoDefaultModel::class);
    }

    protected function registerFileDriver(): void
    {
        Statamic::repository(Contracts\SeoSetConfigRepository::class, \Aerni\AdvancedSeo\Stache\Repositories\SeoSetConfigRepository::class);
        Statamic::repository(Contracts\SeoSetLocalizationRepository::class, \Aerni\AdvancedSeo\Stache\Repositories\SeoSetLocalizationRepository::class);
    }

    protected function bootRouteBindings(): self
    {
        Route::bind('seoSet', function (string $handle, \Illuminate\Routing\Route $route) {
            $type = str($route->uri())
                ->after($route->action['prefix'].'/')
                ->before('/{seoSet}');

            throw_unless(
                $seoSet = Seo::find("{$type}::{$handle}"),
                new \Statamic\Exceptions\NotFoundHttpException("SEO Set [{$type}::{$handle}] not found.")
            );

            return $seoSet;
        });

        Route::bind('seoSetLocalization', function (string $handle, \Illuminate\Routing\Route $route) {
            $type = str($route->uri())
                ->after($route->action['prefix'].'/')
                ->before('/{seoSetLocalization}');

            throw_unless(
                $localization = Seo::find("{$type}::{$handle}")?->in($route->site),
                new \Statamic\Exceptions\NotFoundHttpException("SEO Set Localization [{$type}::{$handle}::{$route->site}] not found.")
            );

            return $localization;
        });

        return $this;
    }

    protected function bootStacheStore(): self
    {
        Stache::registerStores([
            app(SeoSetConfigsStore::class)->directory(config('advanced-seo.directory')),
            app(SeoSetLocalizationsStore::class)->directory(config('advanced-seo.directory')),
        ]);

        return $this;
    }

    protected function bootNav(): self
    {
        Nav::extend(function ($nav) {
            $navItems = Seo::all()
                ->groupBy(fn (SeoSet $default) => $default->type())
                ->filter(fn ($defaults, $type) => User::current()->can('viewAny', [SeoSet::class, $type]))
                ->map(fn ($defaults, $type) => $nav->item(ucfirst($type))->route("advanced-seo.{$type}.index"));

            if ($navItems->isEmpty()) {
                return;
            }

            $nav->tools('SEO')
                ->route('advanced-seo.index')
                ->icon('ai-search-spark')
                ->children($navItems->all());
        });

        return $this;
    }

    protected function bootPermissions(): self
    {
        Permission::extend(function () {
            Permission::group('advanced-seo', 'Advanced SEO', function () {
                Permission::register('configure seo', function ($permission) {
                    $permission
                        ->label('Configure Settings & Defaults')
                        ->description('Grants access to all permissions and allows editing settings and defaults');
                });

                Permission::register('edit seo defaults', function ($permission) {
                    $permission
                        ->label('Edit Defaults')
                        ->description('Grants access to edit collection and taxonomy defaults');
                });
            });
        });

        return $this;
    }

    protected function bootGit(): self
    {
        if (config('statamic.git.enabled')) {
            Git::listen(Events\SeoSetSaved::class);
        }

        return $this;
    }

    protected function bootViewCascade(): self
    {
        View::composer([
            ...Arr::wrap(config('advanced-seo.view_composer', '*')),
            'advanced-seo::head',
            'advanced-seo::body',
            'social_images.*',
        ], CascadeComposer::class);

        return $this;
    }

    protected function bootBladeDirective(): self
    {
        Blade::directive('seo', function ($tag) {
            return "<?php echo \Facades\Aerni\AdvancedSeo\Tags\AdvancedSeoDirective::render($tag, \$__data) ?>";
        });

        return $this;
    }

    protected function bootGraphQL(): self
    {
        if (config('statamic.graphql.enabled') && config('advanced-seo.graphql')) {
            GraphQL::addQuery(SeoSetsQuery::class);
            GraphQL::addQuery(SeoMetaQuery::class);
            GraphQL::addQuery(SeoSitemapsQuery::class);

            GraphQL::addType(AnalyticsDefaultsType::class);
            GraphQL::addType(ComputedMetaDataType::class);
            GraphQL::addType(ContentDefaultsType::class);
            GraphQL::addType(FaviconsDefaultsType::class);
            GraphQL::addType(GeneralDefaultsType::class);
            GraphQL::addType(HreflangType::class);
            GraphQL::addType(IndexingDefaultsType::class);
            GraphQL::addType(RawMetaDataType::class);
            GraphQL::addType(RenderedViewsType::class);
            GraphQL::addType(SeoSetsType::class);
            GraphQL::addType(SeoMetaType::class);
            GraphQL::addType(SeoSitemapsType::class);
            GraphQL::addType(SeoSitemapType::class);
            GraphQL::addType(SiteDefaultsType::class);
            GraphQL::addType(SitemapAlternatesType::class);
            GraphQL::addType(SocialImagePresetType::class);
            GraphQL::addType(SocialMediaDefaultsType::class);

            GraphQL::addField(EntryInterface::NAME, 'seo', fn () => (new SeoField)->toArray());
            GraphQL::addField(TermInterface::NAME, 'seo', fn () => (new SeoField)->toArray());
        }

        return $this;
    }

    protected function bootMigrations(): self
    {
        $this->publishes([
            __DIR__.'/../database/migrations/2025_02_05_100000_create_advanced_seo_defaults_table.php' => database_path('migrations/2025_02_05_100000_create_advanced_seo_defaults_table.php'),
        ], 'advanced-seo-migrations');

        return $this;
    }

    protected function autoPublishConfig(): self
    {
        Statamic::afterInstalled(function ($command) {
            $command->call('vendor:publish', [
                '--tag' => 'advanced-seo-config',
            ]);
        });

        return $this;
    }
}
