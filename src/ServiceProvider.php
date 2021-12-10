<?php

namespace Aerni\AdvancedSeo;

use Aerni\AdvancedSeo\Contracts\SeoDefaultsRepository;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Facades\Defaults;
use Aerni\AdvancedSeo\Stache\SeoStore;
use Aerni\AdvancedSeo\Traits\ShouldHandleRoute;
use Aerni\AdvancedSeo\View\Cascade;
use Illuminate\Support\Facades\View;
use Statamic\Facades\Blink;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Permission;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Stache\Stache;

class ServiceProvider extends AddonServiceProvider
{
    use ShouldHandleRoute;

    protected $actions = [
        Actions\GenerateSocialImages::class,
    ];

    protected $fieldtypes = [
        Fieldtypes\SocialImagesPreviewFieldtype::class,
    ];

    // protected $listen = [
    //     \Aerni\AdvancedSeo\Events\SeoDefaultSetSaved::class => [
    //         \Aerni\AdvancedSeo\Listeners\GenerateFavicons::class,
    //     ],
    // ];

    protected $subscribe = [
        'Aerni\AdvancedSeo\Subscribers\ContentDefaultsSubscriber',
        'Aerni\AdvancedSeo\Subscribers\OnPageSeoBlueprintSubscriber',
        'Aerni\AdvancedSeo\Subscribers\SitemapCacheSubscriber',
        'Aerni\AdvancedSeo\Subscribers\SocialImagesGeneratorSubscriber',
    ];

    protected $tags = [
        Tags\AdvancedSeoTags::class,
    ];

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
        'web' => __DIR__.'/../routes/web.php',
    ];

    protected $scripts = [
        __DIR__.'/../resources/dist/js/cp.js',
    ];

    protected $policies = [
        \Aerni\AdvancedSeo\Data\SeoVariables::class => \Aerni\AdvancedSeo\Policies\SeoVariablesPolicy::class,
    ];

    public function bootAddon(): void
    {
        $this
            ->bootCascade()
            ->bootAddonViews()
            ->bootAddonStores()
            ->bootAddonNav()
            ->bootAddonPermissions();
    }

    public function register(): void
    {
        $this->app->singleton(SeoDefaultsRepository::class, function () {
            $class = \Aerni\AdvancedSeo\Stache\SeoDefaultsRepository::class;

            return new $class($this->app['stache']);
        });
    }

    protected function bootCascade(): self
    {
        // Don't do anything if we're in the CP.
        if (str_contains(request()->path(), config('cp.route', 'cp'))) {
            return $this;
        }

        View::composer('*', function ($view) {
            // We only want to add data if we're on a Statamic frontend route.
            if (! $this->isFrontendRoute()) {
                return;
            }

            $viewData = $view->getData();

            /*
            Cache the cascade because we are removing all `seo_` keys at the end of the callback.
            This means that we only have the necesarry data available to construct the cascade in the first loop iteration.
            */
            $cascade = Blink::once('cascade', function () use ($viewData) {
                return Cascade::make($viewData)->get();
            });

            // Add the seo cascade to the view.
            $view->with('seo', $cascade);

            // Clean up the view data by removing all initial seo keys.
            foreach ($viewData as $key => $value) {
                $view->offsetUnset(str_start($key, 'seo_'));
            }
        });

        return $this;
    }

    protected function bootAddonViews(): self
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'advanced-seo');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/advanced-seo'),
        ], 'advanced-seo-views');

        return $this;
    }

    protected function bootAddonStores(): self
    {
        $seoStore = app(SeoStore::class)->directory(base_path('content/seo'));

        app(Stache::class)->registerStore($seoStore);

        return $this;
    }

    protected function bootAddonNav()
    {
        Nav::extend(function ($nav) {
            $nav->tools('SEO')
                ->can('index', SeoVariables::class)
                ->route('advanced-seo.index')
                ->icon('seo-search-graph')
                ->active('advanced-seo')
                ->children([
                    $nav->item('Site Defaults')
                        ->route('advanced-seo.show', 'site')
                        ->can('siteDefaultsIndex', SeoVariables::class),
                    $nav->item('Content Defaults')
                        ->route('advanced-seo.show', 'content')
                        ->can('contentDefaultsIndex', SeoVariables::class),
                ]);
        });

        return $this;
    }

    protected function bootAddonPermissions(): self
    {
        Permission::group('seo', 'SEO', function () {
            Permission::register('view site defaults', function ($permission) {
                $permission
                    ->label('View Site Defaults')
                    ->children([
                        Permission::make('view {group} defaults')
                            ->label('View :group Defaults')
                            ->replacements('group', function () {
                                return Defaults::site()->map(function ($item) {
                                    return [
                                        'value' => $item['handle'],
                                        'label' => $item['title'],
                                    ];
                                });
                            })
                            ->children([
                                Permission::make('edit {group} defaults')
                                    ->label('Edit :group Defaults'),
                            ]),
                    ]);
            });

            Permission::register('view content defaults', function ($permission) {
                $permission
                    ->label('View Content Defaults')
                    ->children([
                        Permission::make('view {group} defaults')
                            ->label('View :group Defaults')
                            ->replacements('group', function () {
                                return Defaults::content()->map(function ($item) {
                                    return [
                                        'value' => $item['handle'],
                                        'label' => $item['title'],
                                    ];
                                });
                            })
                            ->children([
                                Permission::make('edit {group} defaults')
                                    ->label('Edit :group Defaults'),
                            ]),
                    ]);
            });
        });

        return $this;
    }
}
