<?php

namespace Aerni\AdvancedSeo\Concerns;

use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Collection as StatamicCollection;
use Statamic\Taxonomies\LocalizedTerm;

trait ResolvesTermUrls
{
    /** @return Collection<int, array{site: string, collection: ?string, path: string}> */
    protected function termPathsForSite(LocalizedTerm $term, bool $onlyPublicUrls = true): Collection
    {
        return $term->taxonomy()->collections()
            ->map(fn (StatamicCollection $collection) => (clone $term)->collection($collection))
            ->prepend((clone $term)->collection(null))
            ->filter(fn (LocalizedTerm $localized) => ! $onlyPublicUrls || $this->termIsPublic($localized))
            ->map(fn (LocalizedTerm $localized) => [
                'site' => $localized->locale(),
                'collection' => $localized->collection()?->handle(),
                'path' => $localized->uri(),
            ])
            ->filter(fn (array $path) => $path['path'] !== null)
            ->unique('path')
            ->values();
    }

    /**
     * Attach the collection to a copy so the term being saved is left alone.
     */
    protected function collectionTermPath(LocalizedTerm $term, StatamicCollection $collection): ?string
    {
        $scoped = (clone $term)->collection($collection);

        if (! $this->termIsPublic($scoped)) {
            return null;
        }

        return $scoped->uri();
    }

    protected function termIsPublic(LocalizedTerm $term): bool
    {
        if ($term->status() !== 'published') {
            return false;
        }

        if (! view()->exists($term->template())) {
            return false;
        }

        return ! $term->collection() || $term->taxonomy()->collections()->contains($term->collection());
    }
}
