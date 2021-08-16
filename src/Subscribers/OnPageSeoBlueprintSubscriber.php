<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Jobs\GenerateSocialImageJob;
use Illuminate\Events\Dispatcher;
use Statamic\Events;
use Statamic\Events\Event;

class OnPageSeoBlueprintSubscriber
{
    protected array $events = [
        Events\EntryBlueprintFound::class => 'addFieldsToBlueprint',
        Events\TermBlueprintFound::class => 'addFieldsToBlueprint',
        Events\EntrySaved::class => 'generateSocialImage',
    ];

    public function subscribe(Dispatcher $events): void
    {
        foreach ($this->events as $event => $method) {
            $events->listen($event, [self::class, $method]);
        }
    }

    public function addFieldsToBlueprint(Event $event): void
    {
        $event->blueprint->ensureFieldInSection('seo', [
            'type' => 'advanced_seo',
            'listable' => false,
            'display' => 'Advanced SEO',
        ], 'SEO');
    }

    public function generateSocialImage(Event $event): void
    {
        GenerateSocialImageJob::dispatch($event->entry);
    }
}
