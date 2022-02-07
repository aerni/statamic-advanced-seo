<?php

namespace Aerni\AdvancedSeo;

use Statamic\Facades\Blink;
use Statamic\Stache\Stache;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Permission;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\View\Cascade;
use Illuminate\Support\Facades\View;
use Aerni\AdvancedSeo\Stache\SeoStore;
use Aerni\AdvancedSeo\Facades\Defaults;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Statamic\Providers\AddonServiceProvider;
use Aerni\AdvancedSeo\Concerns\ShouldHandleRoute;
use Aerni\AdvancedSeo\Contracts\SeoDefaultsRepository;

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
        // Don't do anything if we're in the Statamic CP.
        if ($this->isCpRoute()) {
            return $this;
        }

        View::composer('*', function ($view) {
            // We only want to add data if we're on a Statamic frontend route.
            if (! $this->isFrontendRoute()) {
                return;
            }

            $context = collect($view->getData());

            if (! $this->shouldComposeSeoCascade($context)) {
                return;
            }

            /*
            Cache the cascade because we are removing all `seo_` keys at the end of the callback.
            This means that we only have the necesarry data available to construct the cascade in the first loop iteration.
            */
            $cascade = Blink::once('cascade', function () use ($context) {
                return Cascade::from($context)
                    ->withSiteDefaults()
                    ->withPageData()
                    ->withComputedData()
                    ->process()
                    ->get();
            });

            // Add the seo cascade to the view.
            $view->with('seo', $cascade);

            // Clean up the view data by removing all initial seo keys.
            foreach ($context as $key => $value) {
                $view->offsetUnset(str_start($key, 'seo_'));
            }
        });

        return $this;
    }

    protected function shouldComposeSeoCascade(Collection $context): bool
    {
        // Don't add data for collections that are excluded in the config.
        if ($context->has('is_entry') && in_array($context->get('collection')->handle(), config('advanced-seo.excluded_collections', []))) {
            return false;
        }

        // Don't add data for taxonomy terms that are excluded in the config.
        if ($context->has('is_term') && in_array($context->get('taxonomy')->handle(), config('advanced-seo.excluded_taxonomies', []))) {
            return false;
        }

        // Don't add data for taxonomies that are excluded in the config.
        if ($context->has('terms') && in_array($context->get('handle'), config('advanced-seo.excluded_taxonomies', []))) {
            return false;
        }

        return true;
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

    protected function bootAddonNav(): self
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
