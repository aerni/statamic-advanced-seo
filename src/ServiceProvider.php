<?php

namespace Aerni\AdvancedSeo;

use Aerni\AdvancedSeo\Cascades\CascadeComposer;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Gates\SeoContentGate;
use Aerni\AdvancedSeo\GraphQL\Enums\SitemapTypeEnum;
use Aerni\AdvancedSeo\GraphQL\Fields\SeoField;
use Aerni\AdvancedSeo\GraphQL\Queries\SeoMetaQuery;
use Aerni\AdvancedSeo\GraphQL\Queries\SeoSetQuery;
use Aerni\AdvancedSeo\GraphQL\Queries\SeoSitemapsQuery;
use Aerni\AdvancedSeo\GraphQL\Types\CollectionSetType;
use Aerni\AdvancedSeo\GraphQL\Types\ComputedMetaDataType;
use Aerni\AdvancedSeo\GraphQL\Types\HreflangType;
use Aerni\AdvancedSeo\GraphQL\Types\RawMetaDataType;
use Aerni\AdvancedSeo\GraphQL\Types\RenderedViewsType;
use Aerni\AdvancedSeo\GraphQL\Types\SeoMetaType;
use Aerni\AdvancedSeo\GraphQL\Types\SeoSetType;
use Aerni\AdvancedSeo\GraphQL\Types\SitemapAlternatesType;
use Aerni\AdvancedSeo\GraphQL\Types\SitemapType;
use Aerni\AdvancedSeo\GraphQL\Types\SitemapUrlType;
use Aerni\AdvancedSeo\GraphQL\Types\SiteSetType;
use Aerni\AdvancedSeo\GraphQL\Types\SocialImagePresetType;
use Aerni\AdvancedSeo\GraphQL\Types\TaxonomySetType;
use Aerni\AdvancedSeo\SeoSets\SeoSet;
use Aerni\AdvancedSeo\SeoSets\SeoSetGroup;
use Aerni\AdvancedSeo\Stache\Repositories\SeoSetConfigRepository;
use Aerni\AdvancedSeo\Stache\Repositories\SeoSetLocalizationRepository;
use Aerni\AdvancedSeo\Stache\Stores\SeoSetConfigsStore;
use Aerni\AdvancedSeo\Stache\Stores\SeoSetLocalizationsStore;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Git;
use Statamic\Facades\GraphQL;
use Statamic\Facades\Permission;
use Statamic\Facades\Stache;
use Statamic\Facades\User;
use Statamic\GraphQL\Types\EntryInterface;
use Statamic\GraphQL\Types\TermInterface;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    protected $actions = [
        Actions\Statamic\GenerateSocialImages::class,
    ];

    protected $policies = [
        SeoSet::class => Policies\SeoSetPolicy::class,
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
            ->bootStacheStore()
            ->bootRouteBindings()
            ->bootNav()
            ->bootPermissions()
            ->bootGit()
            ->bootViewCascade()
            ->bootBladeDirective()
            ->bootGraphQL()
            ->bootMigrations()
            ->bootInertiaSharedData();
    }

    public function register(): void
    {
        /**
         * Allow these classes to be safely unserialized from Laravel's cache,
         * so that Stache reads of our items rehydrate as real objects.
         */
        $this->registerSerializableClasses([
            SeoSets\SeoSetConfig::class,
            SeoSets\SeoSetLocalization::class,
        ]);

        app()->instance('advanced-seo.tokens', collect());
        app()->instance('advanced-seo.sitemaps', collect());

        Features\EloquentDriver::enabled() ? $this->registerEloquentDriver() : $this->registerFileDriver();
    }

    protected function registerEloquentDriver(): void
    {
        Statamic::repository(Contracts\SeoSetConfigRepository::class, Eloquent\SeoSetConfigRepository::class);
        Statamic::repository(Contracts\SeoSetLocalizationRepository::class, Eloquent\SeoSetLocalizationRepository::class);

        $this->app->bind('statamic.eloquent.seo_set_config.model', Eloquent\SeoSetConfigModel::class);
        $this->app->bind('statamic.eloquent.seo_set_localization.model', Eloquent\SeoSetLocalizationModel::class);
    }

    protected function registerFileDriver(): void
    {
        Statamic::repository(Contracts\SeoSetConfigRepository::class, SeoSetConfigRepository::class);
        Statamic::repository(Contracts\SeoSetLocalizationRepository::class, SeoSetLocalizationRepository::class);
    }

    protected function bootStacheStore(): self
    {
        Stache::registerStores([
            app(SeoSetConfigsStore::class)->directory(config('advanced-seo.directory')),
            app(SeoSetLocalizationsStore::class)->directory(config('advanced-seo.directory')),
        ]);

        return $this;
    }

    protected function bootRouteBindings(): self
    {
        Route::bind('seoSetGroup', function (string $type) {
            return throw_unless(
                Seo::groups()->first(fn (SeoSetGroup $group) => $group->type() === $type),
                new NotFoundHttpException
            );
        });

        Route::bind('seoSet', function (string $handle, \Illuminate\Routing\Route $route) {
            return throw_unless(
                $route->seoSetGroup->seoSets()->filter(fn (SeoSet $set) => $set->handle() === $handle)->first(),
                new NotFoundHttpException
            );
        });

        Route::bind('seoSetLocalization', function (string $site, \Illuminate\Routing\Route $route) {
            return throw_unless(
                $route->seoSet->in($site),
                new NotFoundHttpException
            );
        });

        return $this;
    }

    protected function bootNav(): self
    {
        Nav::extend(function ($nav) {
            $navItems = Seo::groups()
                ->filter(fn (SeoSetGroup $group) => User::current()->can('viewAny', [SeoSet::class, $group]))
                ->map(fn (SeoSetGroup $group) => $nav->item($group->title())->url($group->url()));

            if ($navItems->isEmpty()) {
                return;
            }

            $nav->tools('SEO')
                ->route('advanced-seo.dashboard')
                ->icon('ai-search-spark')
                ->children($navItems->all());
        });

        return $this;
    }

    protected function bootPermissions(): self
    {
        Gate::define('seo.edit-content', [SeoContentGate::class, 'editContent']);

        if (! AdvancedSeo::pro()) {
            return $this;
        }

        Permission::extend(function () {
            Permission::group('advanced-seo', 'Advanced SEO', function () {
                Permission::register('configure seo', function ($permission) {
                    $permission
                        ->label(__('advanced-seo::messages.permission_configure_seo'))
                        ->description(__('advanced-seo::messages.permission_configure_seo_description'));
                });

                Permission::register('edit seo defaults', function ($permission) {
                    $permission
                        ->label(__('advanced-seo::messages.permission_edit_defaults'))
                        ->description(__('advanced-seo::messages.permission_edit_defaults_description'));
                });

                Permission::register('edit seo content', function ($permission) {
                    $permission
                        ->label(__('advanced-seo::messages.permission_edit_content'))
                        ->description(__('advanced-seo::messages.permission_edit_content_description'));
                });
            });
        });

        return $this;
    }

    protected function bootGit(): self
    {
        if (config('statamic.git.enabled')) {
            Git::listen(Events\SeoSetConfigSaved::class);
            Git::listen(Events\SeoSetConfigDeleted::class);
            Git::listen(Events\SeoSetLocalizationSaved::class);
            Git::listen(Events\SeoSetLocalizationDeleted::class);
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
        if (Features\GraphQL::enabled()) {
            GraphQL::addQuery(SeoSetQuery::class);
            GraphQL::addQuery(SeoMetaQuery::class);
            GraphQL::addQuery(SeoSitemapsQuery::class);

            GraphQL::addType(CollectionSetType::class);
            GraphQL::addType(ComputedMetaDataType::class);
            GraphQL::addType(HreflangType::class);
            GraphQL::addType(RawMetaDataType::class);
            GraphQL::addType(RenderedViewsType::class);
            GraphQL::addType(SeoMetaType::class);
            GraphQL::addType(SeoSetType::class);
            GraphQL::addType(SitemapAlternatesType::class);
            GraphQL::addType(SitemapType::class);
            GraphQL::addType(SitemapTypeEnum::class);
            GraphQL::addType(SitemapUrlType::class);
            GraphQL::addType(SiteSetType::class);
            GraphQL::addType(SocialImagePresetType::class);
            GraphQL::addType(TaxonomySetType::class);

            GraphQL::addField(EntryInterface::NAME, 'seo', fn () => (new SeoField)->toArray());
            GraphQL::addField(TermInterface::NAME, 'seo', fn () => (new SeoField)->toArray());
        }

        return $this;
    }

    protected function bootMigrations(): self
    {
        $this->publishes([
            __DIR__.'/../database/migrations/2026_01_13_100000_create_seo_set_configs_table.php' => database_path('migrations/2026_01_13_100000_create_seo_set_configs_table.php'),
            __DIR__.'/../database/migrations/2026_01_13_100001_create_seo_set_localizations_table.php' => database_path('migrations/2026_01_13_100001_create_seo_set_localizations_table.php'),
            __DIR__.'/../database/migrations/2026_01_13_100002_migrate_seo_defaults_to_new_tables.php' => database_path('migrations/2026_01_13_100002_migrate_seo_defaults_to_new_tables.php'),
        ], 'advanced-seo-migrations');

        return $this;
    }

    protected function bootInertiaSharedData(): self
    {
        Inertia::share([
            'advancedSeo' => fn () => [
                'promoteUpgrade' => AdvancedSeo::shouldPromoteUpgrade(),
                'proFeatures' => AdvancedSeo::proFeatures(),
            ],
        ]);

        return $this;
    }
}
