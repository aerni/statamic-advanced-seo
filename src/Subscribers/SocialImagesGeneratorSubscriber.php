<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Actions\DeleteSocialImages;
use Aerni\AdvancedSeo\Actions\ShouldDisplaySocialImagesGenerator;
use Aerni\AdvancedSeo\Actions\ShouldGenerateSocialImages;
use Aerni\AdvancedSeo\Concerns\GetsEventData;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Jobs\GenerateSocialImagesJob;
use Illuminate\Events\Dispatcher;
use Statamic\Events;
use Statamic\Events\Event;

class SocialImagesGeneratorSubscriber
{
    use GetsEventData;

    public function subscribe(Dispatcher $events): array
    {
        return [
            Events\EntrySaved::class => 'generateSocialImages',
            // Events\TermSaved::class => 'generateSocialImages', // TODO: This event does not currently exist but might be added with this PR: https://github.com/statamic/cms/pull/3379
            // Events\EntryBlueprintFound::class => 'addPreviewTargets',
            // Events\TermBlueprintFound::class => 'addPreviewTargets',
        ];
    }

    public function generateSocialImages(Event $event): void
    {
        ShouldGenerateSocialImages::handle($event->entry)
            ? GenerateSocialImagesJob::dispatch($event->entry)
            : DeleteSocialImages::handle($event->entry);
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
