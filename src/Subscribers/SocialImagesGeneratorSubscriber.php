<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Concerns\GetsEventData;
use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Features\SocialImagesGenerator;
use Aerni\AdvancedSeo\Jobs\DeleteSocialImagesJob;
use Aerni\AdvancedSeo\Jobs\GenerateSocialImagesJob;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Events;
use Statamic\Events\Event;
use Statamic\Facades\CP\Toast;
use Statamic\Statamic;

class SocialImagesGeneratorSubscriber
{
    use GetsEventData;

    public function subscribe(Dispatcher $events): array
    {
        return [
            Events\EntrySaved::class => 'generateSocialImages',
            Events\LocalizedTermSaved::class => 'generateSocialImages',
            Events\EntryBlueprintFound::class => 'addPreviewTargets',
            Events\TermBlueprintFound::class => 'addPreviewTargets',
        ];
    }

    public function generateSocialImages(Event $event): void
    {
        $content = $this->getProperty($event);
        $context = $this->resolveEventContext($event);

        if (! $this->shouldGenerateSocialImages($content, $context)) {
            return;
        }

        // Delete the images so we can create a new one on the next request.
        if (! config('advanced-seo.social_images.generator.generate_on_save', true)) {
            DeleteSocialImagesJob::dispatch($content);

            return;
        }

        // Show a toast message if we are using the queue.
        if (config('queue.default') !== 'sync') {
            Toast::info(__('advanced-seo::messages.social_images_generator_generating_queue'));
        }

        GenerateSocialImagesJob::dispatch($content);
    }

    public function addPreviewTargets(Event $event): void
    {
        $content = $this->getProperty($event);
        $context = $this->resolveEventContext($event);

        if (! $this->shouldAddPreviewTargets($content, $context)) {
            return;
        }

        $context->parent->addPreviewTargets(SocialImage::previewTargets($content));
    }

    protected function shouldGenerateSocialImages(Entry|Term $content, Context $context): bool
    {
        // Don't generate if the social images generator feature is disabled.
        if (! SocialImagesGenerator::enabled($context)) {
            return false;
        }

        // Don't generate if the content is saved when first localizing.
        if (Statamic::isCpRoute() && Str::contains(request()->path(), 'localize')) {
            return false;
        }

        // Don't generate if the content is saved when an action is performed on the listing view.
        if (Statamic::isCpRoute() && Str::contains(request()->path(), 'actions')) {
            return false;
        }

        // Only generate if the social images generator is turned on for this content.
        return $content->seo_generate_social_images;
    }

    protected function shouldAddPreviewTargets(Entry|Term|null $content, Context $context): bool
    {
        // Only add preview targets in the CP.
        if (! Statamic::isCpRoute()) {
            return false;
        }

        // Only add preview targets when editing existing content.
        if (! $content?->id()) {
            return false;
        }

        // Only add preview targets when the generator is enabled.
        return SocialImagesGenerator::enabled($context);
    }
}
