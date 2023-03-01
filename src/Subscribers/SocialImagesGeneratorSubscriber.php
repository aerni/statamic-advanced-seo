<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Actions\DeleteSocialImages;
use Aerni\AdvancedSeo\Actions\ShouldGenerateSocialImages;
use Aerni\AdvancedSeo\Concerns\GetsEventData;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Features\SocialImagesGenerator;
use Aerni\AdvancedSeo\Jobs\GenerateSocialImagesJob;
use Illuminate\Events\Dispatcher;
use Statamic\Events;
use Statamic\Events\Event;
use Statamic\Facades\CP\Toast;

class SocialImagesGeneratorSubscriber
{
    use GetsEventData;

    public function subscribe(Dispatcher $events): array
    {
        return [
            Events\EntrySaved::class => 'generateSocialImages',
            // Events\TermSaved::class => 'generateSocialImages', // TODO: This event does not currently exist but might be added with this PR: https://github.com/statamic/cms/pull/3379
            Events\EntryBlueprintFound::class => 'addPreviewTargets',
            // Events\TermBlueprintFound::class => 'addPreviewTargets',
        ];
    }

    public function generateSocialImages(Event $event): void
    {
        if (! ShouldGenerateSocialImages::handle($event->entry)) {
            return;
        }

        // Delete the images so we can create a new one on the next request.
        if (! config('advanced-seo.social_images.generator.generate_on_save', true)) {
            DeleteSocialImages::handle($event->entry);

            return;
        }

        // Show a toast message if we are using the queue.
        if (config('queue.default') !== 'sync') {
            Toast::info(__('advanced-seo::messages.social_images_generator_generating_queue'));
        }

        GenerateSocialImagesJob::dispatch($event->entry);
    }

    public function addPreviewTargets(Event $event): void
    {
        $data = $this->getDataFromEvent($event);

        if (! SocialImagesGenerator::enabled($data)) {
            return;
        }

        // TODO: This has to change when implementing for taxonomies.
        $this->getProperty($event)?->collection()->addPreviewTargets(SocialImage::previewTargets());
    }
}
