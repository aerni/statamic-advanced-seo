<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Jobs\GenerateSocialImagesJob;
use Statamic\Actions\Action;
use Statamic\Contracts\Entries\Entry;

class GenerateSocialImages extends Action
{
    public function visibleTo($item): bool
    {
        // Shouldn't generate if the generator was disabled in the config.
        if (! config('advanced-seo.social_images.generator.enabled', false)) {
            return false;
        }

        // Don't proceed if the item isn't an entry.
        if (! $item instanceof Entry) {
            return false;
        }

        // Get the collections that are allowed to generate social images.
        $enabledCollections = Seo::find('site', 'social_media')
            ?->in($item->site()->handle())
            ?->value('social_images_generator_collections') ?? [];


        return in_array($item->collectionHandle(), $enabledCollections);
    }

    public function authorize($user, $item): bool
    {
        return $user->can('edit', $item);
    }

    public function run($items, $values): string
    {
        GenerateSocialImagesJob::dispatch($items);

        $queue = config('advanced-seo.social_images.generator.queue');
        $driver = config("queue.connections.$queue.driver");

        return $driver === 'sync'
            ? trans_choice('advanced-seo::messages.social_images', $items)
            : trans_choice('advanced-seo::messages.social_images_queue', $items);
    }
}
