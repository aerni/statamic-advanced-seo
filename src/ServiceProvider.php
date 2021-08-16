<?php

namespace Aerni\AdvancedSeo;

use Statamic\Facades\CP\Nav;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $fieldtypes = [
        Fieldtypes\AdvancedSeoFieldtype::class,
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

    public function boot(): void
    {
        parent::boot();

        $this
            ->bootAddonViews()
            ->bootAddonNav();
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
                ->route('advanced-seo.general.index')
                ->icon('seo-search-graph')
                ->active('advanced-seo');
        });

        return $this;
    }
}
