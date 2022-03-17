<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Actions\ShouldDisplaySocialImagesGenerator;
use Statamic\Events;
use Statamic\Events\Event;
use Illuminate\Events\Dispatcher;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Jobs\GenerateSocialImageJob;
use Aerni\AdvancedSeo\Actions\ShouldGenerateSocialImages;
use Aerni\AdvancedSeo\Concerns\GetsEventData;

class SocialImagesGeneratorSubscriber
{
    use GetsEventData;

    protected array $events = [
        Events\EntrySaved::class => 'generateSocialImages',
        // Events\TermSaved::class => 'generateSocialImages', // TODO: This event does not currently exist but might be added with this PR: https://github.com/statamic/cms/pull/3379
        Events\EntryBlueprintFound::class => 'addPreviewTargets',
        // Events\TermBlueprintFound::class => 'addPreviewTargets',
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

    public function addPreviewTargets(Event $event): void
    {
        $data = $this->getDataFromEvent($event);

        if (! ShouldDisplaySocialImagesGenerator::handle($data)) {
            return;
        }

        // TODO: This has to change when implementing for taxonomies.
        $this->getProperty($event)?->collection()->extraPreviewTargets(SocialImage::previewTargets());
    }
}
