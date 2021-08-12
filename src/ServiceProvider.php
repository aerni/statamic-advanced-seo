<?php

namespace Aerni\AdvancedSeo;

use Aerni\AdvancedSeo\Commands\SetupSeo;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $commands = [
        SetupSeo::class,
    ];

    protected $listen = [
        'Statamic\Events\EntryBlueprintFound' => [
            'Aerni\AdvancedSeo\Listeners\AppendSeoEntryBlueprint',
        ],
        'Statamic\Events\EntrySaved' => [
            'Aerni\AdvancedSeo\Listeners\GenerateSocialImage',
        ],
        'Statamic\Events\GlobalVariablesBlueprintFound' => [
            'Aerni\AdvancedSeo\Listeners\AppendSeoGlobalsBlueprint',
        ],
    ];

    protected $tags = [
        Tags\AdvancedSeoTags::class,
    ];

    protected $routes = [
        'web' => __DIR__.'/../routes/web.php',
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
