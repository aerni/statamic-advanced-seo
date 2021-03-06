<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Facades\Sitemap;
use Illuminate\Events\Dispatcher;
use Statamic\Events\Event;

class SitemapCacheSubscriber
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            \Statamic\Events\CollectionSaved::class => 'clearCache',
            \Statamic\Events\EntrySaved::class => 'clearCache',
            \Statamic\Events\EntryDeleted::class => 'clearCache',
            \Statamic\Events\TaxonomySaved::class => 'clearCache',
            \Statamic\Events\TermSaved::class => 'clearCache',
            \Statamic\Events\TermDeleted::class => 'clearCache',
            \Aerni\AdvancedSeo\Events\SeoDefaultSetSaved::class => 'clearCache',
        ];
    }

    public function clearCache(Event $event): void
    {
        Sitemap::clearCache();
    }
}
