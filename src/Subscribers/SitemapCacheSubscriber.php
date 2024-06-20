<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Facades\Sitemap;
use Illuminate\Events\Dispatcher;
use Statamic\Events\Event;

class SitemapCacheSubscriber
{
    // TODO: Can we queue this?
    public function subscribe(Dispatcher $events): array
    {
        return [
            \Statamic\Events\CollectionSaved::class => 'refreshCache',
            \Statamic\Events\EntrySaved::class => 'refreshCache',
            \Statamic\Events\EntryDeleted::class => 'refreshCache',
            \Statamic\Events\TaxonomySaved::class => 'refreshCache',
            \Statamic\Events\TermSaved::class => 'refreshCache',
            \Statamic\Events\TermDeleted::class => 'refreshCache',
            \Aerni\AdvancedSeo\Events\SeoDefaultSetSaved::class => 'refreshCache',
        ];
    }

    public function refreshCache(Event $event): void
    {
        Sitemap::refreshCache();
    }
}
