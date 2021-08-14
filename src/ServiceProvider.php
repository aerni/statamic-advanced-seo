<?php

namespace Aerni\AdvancedSeo;

use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $commands = [
        Commands\SetupSeo::class,
    ];

    protected $fieldtypes = [
        Fieldtypes\AdvancedSeoFieldtype::class,
    ];

    protected $listen = [
        'Statamic\Events\EntrySaved' => [
            'Aerni\AdvancedSeo\Listeners\GenerateSocialImage',
        ],
        'Statamic\Events\GlobalVariablesBlueprintFound' => [
            'Aerni\AdvancedSeo\Listeners\AppendSeoGlobalsBlueprint',
        ],
    ];

    protected $subscribe = [
        'Aerni\AdvancedSeo\Subscribers\BlueprintSubscriber',
    ];

    protected $tags = [
        Tags\AdvancedSeoTags::class,
    ];

    protected $routes = [
        'web' => __DIR__.'/../routes/web.php',
    ];

    protected $scripts = [
        __DIR__.'/../resources/dist/js/cp.js',
    ];

    public function boot(): void
    {
        parent::boot();

        $this->bootAddonViews();
    }

    protected function bootAddonViews(): self
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'advanced-seo');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/advanced-seo'),
        ], 'advanced-seo-views');

        return $this;
    }
}
