<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Statamic\Events;
use Statamic\Statamic;
use Statamic\Events\Event;
use Statamic\Facades\Site;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Statamic\Facades\CP\Toast;
use Illuminate\Events\Dispatcher;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Concerns\GetsEventData;
use Aerni\AdvancedSeo\Jobs\DeleteSocialImagesJob;
use Aerni\AdvancedSeo\Jobs\GenerateSocialImagesJob;
use Aerni\AdvancedSeo\Features\SocialImagesGenerator;
use Aerni\AdvancedSeo\Actions\ShouldGenerateSocialImages;

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
            DeleteSocialImagesJob::dispatch($event->entry);

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
        if (! $this->shouldAddPreviewTargets($event)) {
            return;
        }

        $this->getProperty($event)?->collection()->addPreviewTargets(SocialImage::previewTargets($event->entry));
    }

    protected function shouldAddPreviewTargets(Event $event): bool
    {
        // Only add preview targets in the CP.
        if (! Statamic::isCpRoute()) {
            return false;
        }

        // Only add preview targets when editing an existing entry.
        if (! Str::containsAll(request()->path(), ['collections', $event->entry?->id()])) {
            return false;
        }

        // Only add preview targets when the generator is enabled.
        return SocialImagesGenerator::enabled($this->getDataFromEvent($event));
    }
}
