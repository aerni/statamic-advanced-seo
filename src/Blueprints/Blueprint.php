<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Statamic\Events;
use Statamic\Events\Event;
use Statamic\Fields\Blueprint as StatamicBlueprint;

class Blueprint
{
    protected StatamicBlueprint $blueprint;
    protected mixed $data;

    public function __construct(Event $event)
    {
        $this->blueprint = $event->blueprint;
        $this->data = $this->getEventData($event);
    }

    public static function on(Event $event): self
    {
        return new static($event);
    }

    public function addSeoFields(): void
    {
        $this->blueprint->ensureFieldInSection('seo', [
            'type' => 'advanced_seo',
            'listable' => false,
            'display' => 'Advanced SEO',
        ], 'SEO');
    }

    protected function getEventData(Event $event): mixed
    {
        $eventClass = get_class($event);

        $eventDataProperties = [
            Events\EntryBlueprintFound::class => 'entry',
            Events\TermBlueprintFound::class => 'term',
        ];

        $eventDataProperty = $eventDataProperties[$eventClass];

        return $event->{$eventDataProperty};
    }
}
