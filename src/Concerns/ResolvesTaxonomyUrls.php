<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Actions\ViewExistsForSite;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Facades\Taxonomy as TaxonomyFacade;
use Statamic\Facades\URL;

trait ResolvesTaxonomyUrls
{
    /**
     * A taxonomy index path on this site. Supply the saved mount path when
     * capturing a URL before the mount is rewritten. Deletion cleanup also
     * needs paths whose templates have since been removed.
     */
    protected function taxonomyIndexPath(Taxonomy $taxonomy, string $site, ?string $mountPath = null, bool $onlyPublicUrls = true): ?string
    {
        $collection = $taxonomy->collection();

        if ($onlyPublicUrls) {
            if (! $taxonomy->sites()->contains($site) || ($collection && ! $collection->sites()->contains($site))) {
                return null;
            }

            if ($collection && ! $taxonomy->collections()->contains($collection)) {
                return null;
            }

            if (! ViewExistsForSite::run($taxonomy->template(), $site)) {
                return null;
            }
        }

        $prefix = $collection ? ($mountPath ?? $collection->uri($site) ?? $collection->handle()) : '/';

        return URL::tidy(url: $prefix.'/'.str_replace('_', '-', $taxonomy->handle()), withTrailingSlash: false);
    }

    protected function taxonomyIndexOwnsPath(string $path, string $site): bool
    {
        $taxonomy = TaxonomyFacade::findByUri($path, $site);

        return $taxonomy && $this->taxonomyIndexPath($taxonomy, $site) === $path;
    }
}
