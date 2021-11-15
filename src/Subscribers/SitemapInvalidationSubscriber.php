<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Facades\Sitemap;
use Aerni\AdvancedSeo\Traits\GetsEventData;
use Illuminate\Events\Dispatcher;
use Statamic\Events\Event;
use Statamic\Facades\Site;

class SitemapInvalidationSubscriber
{
    use GetsEventData;

    protected array $events = [
        \Statamic\Events\EntrySaved::class => 'invalidateSitemaps',
        \Statamic\Events\EntryDeleted::class => 'invalidateSitemaps',
        \Statamic\Events\TermSaved::class => 'invalidateSitemaps',
        \Statamic\Events\TermDeleted::class => 'invalidateSitemaps',
    ];

    public function subscribe(Dispatcher $events): void
    {
        foreach ($this->events as $event => $method) {
            $events->listen($event, [self::class, $method]);
        }
    }

    public function invalidateSitemaps(Event $event): void
    {
        if ($this->determineProperty($event) === 'entry') {
            $site = $event->entry->locale();
            $type = 'collections';
            $handle = $event->entry->collection()->handle();

            Sitemap::clearCache($site, $type, $handle);
        } else {
            // Taxonomies are not localizable yet. Because we can't get the locale of a single term
            // we have to invalidate the cache for all existing sites.
            Site::all()->map(function ($site) use ($event) {
                $site = $site->handle();
                $type = 'taxonomies';
                $handle = $event->term->taxonomy()->handle();

                Sitemap::clearCache($site, $type, $handle);
            });
        }
    }
}
