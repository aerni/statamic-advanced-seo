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
        Tags\SocialImageTag::class,
    ];

    protected $routes = [
        'web' => __DIR__.'/../routes/web.php',
    ];
}
