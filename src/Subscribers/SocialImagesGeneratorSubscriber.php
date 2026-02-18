<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Concerns\GetsEventData;
use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Features\SocialImagesGenerator;
use Aerni\AdvancedSeo\Jobs\GenerateSocialImagesJob;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Events;
use Statamic\Events\Event;
use Statamic\Statamic;

use function Illuminate\Support\defer;

class SocialImagesGeneratorSubscriber
{
    use GetsEventData;

    public function subscribe(Dispatcher $events): array
    {
        return [
            Events\EntrySaved::class => 'generateSocialImages',
            Events\LocalizedTermSaved::class => 'generateSocialImages',
        ];
    }

    public function generateSocialImages(Event $event): void
    {
        $content = $this->getProperty($event);
        $context = $this->resolveEventContext($event);

        if (! $this->shouldHandleSocialImages($content, $context)) {
            return;
        }

        $generator = SocialImage::openGraph()->for($content);

        // Generate and persist the social image if it's dirty.
        if ($generator->isDirty()) {
            defer(fn () => GenerateSocialImagesJob::dispatch($content));
            return;
        }

        // Ensure the existing asset is persisted.
        $content->set('seo_og_image', $generator->asset()->path());
        $content->saveQuietly();
    }

    protected function shouldHandleSocialImages(Entry|Term $content, Context $context): bool
    {
        // Don't handle if the social images generator feature is disabled.
        if (! SocialImagesGenerator::enabled($context)) {
            return false;
        }

        // Don't handle if the content is saved when first localizing.
        if (Statamic::isCpRoute() && Str::contains(request()->path(), 'localize')) {
            return false;
        }

        // Don't handle if the content is saved when an action is performed on the listing view.
        if (Statamic::isCpRoute() && Str::contains(request()->path(), 'actions')) {
            return false;
        }

        // Only handle if the social images generator is turned on for this content.
        return $content->seo_generate_social_images;
    }
}
