<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Blueprints\Blueprint;
use Illuminate\Events\Dispatcher;
use Statamic\Events;
use Statamic\Events\Event;

class BlueprintSubscriber
{
    protected array $events = [
        Events\EntryBlueprintFound::class => 'addFieldsToBlueprint',
        Events\TermBlueprintFound::class => 'addFieldsToBlueprint',
    ];

    public function subscribe(Dispatcher $events): void
    {
        foreach ($this->events as $event => $method) {
            $events->listen($event, [self::class, $method]);
        }
    }

    public function addFieldsToBlueprint(Event $event): void
    {
        Blueprint::on($event)->addSeoFields();
    }
}
