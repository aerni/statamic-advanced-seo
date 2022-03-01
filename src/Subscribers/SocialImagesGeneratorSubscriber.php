<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Actions\ShouldGenerateSocialImages;
use Aerni\AdvancedSeo\Jobs\GenerateSocialImageJob;
use Illuminate\Events\Dispatcher;
use Statamic\Events;
use Statamic\Events\Event;

class SocialImagesGeneratorSubscriber
{
    protected array $events = [
        Events\EntrySaved::class => 'generateSocialImages',
        // Events\TermSaved::class => 'generateSocialImages', // TODO: This event does not currently exist but might be added with this PR: https://github.com/statamic/cms/pull/3379
    ];

    public function subscribe(Dispatcher $events): void
    {
        foreach ($this->events as $event => $method) {
            $events->listen($event, [self::class, $method]);
        }
    }

    public function generateSocialImages(Event $event): void
    {
        if (! ShouldGenerateSocialImages::handle($event->entry)) {
            return;
        }

        GenerateSocialImageJob::dispatch($event->entry);
    }
}
