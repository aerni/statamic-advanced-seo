<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Facades\Sitemap;
use Illuminate\Events\Dispatcher;
use Statamic\Events\Event;

class SitemapInvalidationSubscriber
{
    protected array $events = [
        \Statamic\Events\CollectionSaved::class => 'clearCache',
        \Statamic\Events\EntrySaved::class => 'clearCache',
        \Statamic\Events\EntryDeleted::class => 'clearCache',
        \Statamic\Events\TaxonomySaved::class => 'clearCache',
        \Statamic\Events\TermSaved::class => 'clearCache',
        \Statamic\Events\TermDeleted::class => 'clearCache',
        \Aerni\AdvancedSeo\Events\SeoDefaultsSaved::class => 'clearCache',
    ];

    public function subscribe(Dispatcher $events): void
    {
        foreach ($this->events as $event => $method) {
            $events->listen($event, [self::class, $method]);
        }
    }

    public function clearCache(Event $event): void
    {
        Sitemap::clearCache();
    }
}
