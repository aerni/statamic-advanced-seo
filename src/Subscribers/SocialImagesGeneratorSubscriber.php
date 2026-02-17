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

        if (! $this->shouldGenerateSocialImages($content, $context)) {
            return;
        }

        defer(fn () => GenerateSocialImagesJob::dispatch($content));
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
        if (! $content->seo_generate_social_images) {
            return false;
        }

        // Don't generate if the content hasn't changed since the last generation.
        return SocialImage::openGraph()->for($content)->isDirty();
    }
}
