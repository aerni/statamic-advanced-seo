<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Jobs\GenerateSocialImageJob;
use Illuminate\Events\Dispatcher;
use Statamic\Contracts\Entries\Entry;
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
        if (! $this->shouldGenerateSocialImage($event->entry)) {
            return;
        }

        GenerateSocialImageJob::dispatch($event->entry);
    }

    protected function shouldGenerateSocialImage(Entry $entry): bool
    {
        // Don't generate if we're first localiting an entry.
        // TODO: If we support taxonomies add this check as well: str_contains(url()->previous(), 'create')
        if (str_contains(request()->path(), 'localize')) {
            return false;
        };

        // Shouldn't generate if the generator was disabled in the config.
        if (! config('advanced-seo.social_images.generator.enabled', false)) {
            return false;
        }

        // Get the collections that are allowed to generate social images.
        $enabledCollections = Seo::find('site', 'social_media')
            ?->in($entry->site()->handle())
            ?->value('social_images_generator_collections') ?? [];

        // Shouldn't generate if the entry's collection is not selected.
        if (! in_array($entry->collectionHandle(), $enabledCollections)) {
            return false;
        }

        $enabledByDefault = Seo::find('collections', $entry->collection()->handle())
            ?->in($entry->site()->handle())
            ?->value('seo_generate_social_images');

        $enabledOnEntry = $entry->augmentedValue('seo_generate_social_images')->value();

        $enabled = $enabledOnEntry ?? $enabledByDefault;

        return $enabled ? true : false;
    }
}
