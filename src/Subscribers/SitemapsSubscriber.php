<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Facades\Sitemap;
use Statamic\Events;
use Statamic\Events\Event;
use Illuminate\Events\Dispatcher;
use Aerni\AdvancedSeo\Sitemaps\Collections\EntrySitemapUrl;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;

class SitemapsSubscriber
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            Events\EntrySaved::class => 'updateEntrySitemapUrl',
        ];
    }

    public function updateEntrySitemapUrl(Event $event): void
    {
        if (! $event->isInitial()) {
            return;
        }

        $sitemap = Sitemap::find("collection-{$event->entry->collectionHandle()}");

        // TODO: What if there is no file sitemap. Should we simply return or write the file?

        $this->relatives($event->entry)
            ->mapInto(EntrySitemapUrl::class)
            ->each(fn ($url) => $sitemap->updateUrl($url));
    }

    // TODO: Might be a good idea to move this into the EntrySitemapUrl class.
    // So we can just call the updateUrl() method and let that handle updating the relatives as well.
    protected function relatives(Entry $entry): Collection
    {
        return collect()
            ->push($root = $entry->root())
            ->merge($root->descendants())
            ->values();
    }
}
