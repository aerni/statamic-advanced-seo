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
            'Aerni\AdvancedSeo\Listeners\AppendEntryBlueprint',
        ],
        'Statamic\Events\GlobalVariablesBlueprintFound' => [
            'Aerni\AdvancedSeo\Listeners\AppendSeoGlobalsBlueprint',
        ],
    ];
}
