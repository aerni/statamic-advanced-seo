<?php

namespace Aerni\AdvancedSeo;

use Aerni\AdvancedSeo\Contracts\SeoDefaultsRepository;
use Aerni\AdvancedSeo\Stache\SeoStore;
use Statamic\Facades\CP\Nav;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Stache\Stache;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
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

    public function boot(): void
    {
        parent::boot();

        Statamic::booted(function () {
            $this
                ->bootAddonViews()
                ->bootAddonNav()
                ->bootAddonStores();
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

    protected function bootAddonNav()
    {
        Nav::extend(function ($nav) {
            $nav->tools('SEO')
                ->route('advanced-seo.index')
                ->icon('seo-search-graph')
                ->active('advanced-seo')
                ->children([
                    $nav->item('Site Defaults')->route('advanced-seo.site.index'),
                    $nav->item('Content Defaults')->route('advanced-seo.content.index'),
                ]);
        });

        return $this;
    }

    protected function bootAddonStores(): self
    {
        $seoStore = app(SeoStore::class)->directory(base_path('content/seo'));

        app(Stache::class)->registerStore($seoStore);

        return $this;
    }
}
