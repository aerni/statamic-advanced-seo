<?php

namespace Aerni\AdvancedSeo;

use Statamic\Statamic;
use Statamic\Stache\Stache;
use Statamic\Facades\CP\Nav;
use Statamic\Facades\Permission;
use Aerni\AdvancedSeo\Stache\SeoStore;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Statamic\Providers\AddonServiceProvider;
use Aerni\AdvancedSeo\Contracts\SeoDefaultsRepository;

class ServiceProvider extends AddonServiceProvider
{
    protected $fieldtypes = [
        Fieldtypes\SeoMetaTitleFieldtype::class,
    ];

    protected $subscribe = [
        'Aerni\AdvancedSeo\Subscribers\OnPageSeoBlueprintSubscriber',
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

    public function boot(): void
    {
        parent::boot();

        Statamic::booted(function () {
            $this
                ->bootAddonViews()
                ->bootAddonStores()
                ->bootAddonNav()
                ->bootAddonPermissions();
        });
    }

    public function register(): void
    {
        $this->app->singleton(SeoDefaultsRepository::class, function () {
            $class = \Aerni\AdvancedSeo\Stache\SeoDefaultsRepository::class;

            return new $class($this->app['stache']);
        });
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
        // TODO: This could maybe be dynamic with a repository.

        $siteGroup = collect([
            [
                'value' => 'general',
                'label' => 'General',
            ],
            [
                'value' => 'marketing',
                'label' => 'Marketing',
            ],
        ]);

        $contentGroup = collect([
            [
                'value' => 'collection',
                'label' => 'Collection',
            ],
            [
                'value' => 'taxonomy',
                'label' => 'Taxonomy',
            ],
        ]);

        Permission::group('seo', 'SEO', function () use ($siteGroup, $contentGroup) {
            Permission::register('view site defaults', function ($permission) use ($siteGroup) {
                $permission
                    ->label('View Site Defaults')
                    ->children([
                        Permission::make('view {group} defaults')
                            ->label('View :group Defaults')
                            ->replacements('group', function () use ($siteGroup) {
                                return $siteGroup->map(function ($item) {
                                    return [
                                        'value' => $item['value'],
                                        'label' => $item['label'],
                                    ];
                                });
                            })
                            ->children([
                                Permission::make('edit {group} defaults')
                                    ->label('Edit :group Defaults'),
                            ]),
                    ]);
            });
            Permission::register('view content defaults', function ($permission) use ($contentGroup) {
                $permission
                    ->label('View Content Defaults')
                    ->children([
                        Permission::make('view {group} defaults')
                            ->label('View :group Defaults')
                            ->replacements('group', function () use ($contentGroup) {
                                return $contentGroup->map(function ($item) {
                                    return [
                                        'value' => $item['value'],
                                        'label' => $item['label'],
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
