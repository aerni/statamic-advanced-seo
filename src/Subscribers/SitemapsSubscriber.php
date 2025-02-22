<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Statamic\Events;
use Statamic\Events\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Events\Dispatcher;
use Aerni\AdvancedSeo\Facades\Sitemap;
use Illuminate\Contracts\Queue\ShouldQueue;

class SitemapsSubscriber implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->queue = config('advanced-seo.sitemap.queue', 'default');
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            Events\EntrySaved::class => 'updateEntrySitemapUrls',
        ];
    }

    public function updateEntrySitemapUrls(Event $event): void
    {
        if (! $event->isInitial()) {
            return;
        }

        // TODO: Getting the sitemap from the repository can also be quite resource intensive.
        // Maybe it's a better idea to construct a fresh sitemap without using the repository?
        if (! $sitemap = Sitemap::find("collection-{$event->entry->collectionHandle()}")?->loadUrlsFromFile()) {
            return;
        };

        $sitemap->updateUrls($event->entry)->save();
    }
}
