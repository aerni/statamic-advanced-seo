<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Support\Helpers;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\URL;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\Taxonomies\Taxonomy;

trait HasHreflang
{
    use EvaluatesIndexability;

    protected function entryAndTermHreflang(Entry|LocalizedTerm $model): ?array
    {
        if (! $this->shouldIncludeHreflang($model)) {
            return null;
        }

        $sites = $model instanceof Entry
            ? $model->sites()
            : $model->taxonomy()->sites();

        if ($sites->count() < 2) {
            return null;
        }

        $hreflang = $sites
            ->map(fn ($locale) => $model->in($locale))
            ->filter() // A model might not exist in a site. So we need to remove it to prevent calling methods on null
            ->filter($this->shouldIncludeHreflang(...));

        if ($hreflang->count() < 2) {
            return null;
        }

        $hreflang->transform(fn ($model) => [
            'url' => $model->absoluteUrl(),
            'locale' => Helpers::parseLocale($model->site()->locale()),
        ]);

        $origin = $model->origin() ?? $model;

        $xDefault = $this->shouldIncludeHreflang($origin) ? $origin : $model;

        return $hreflang->push([
            'url' => $xDefault->absoluteUrl(),
            'locale' => 'x-default',
        ])->values()->all();
    }

    protected function taxonomyHreflang(Taxonomy $taxonomy): ?array
    {
        $initialSite = Site::current()->handle();

        try {
            return $this->buildTaxonomyHreflang($taxonomy, function ($site) use ($taxonomy) {
                Site::setCurrent($site);

                return $taxonomy->absoluteUrl();
            });
        } finally {
            Site::setCurrent($initialSite);
        }
    }

    protected function collectionTaxonomyHreflang(Taxonomy $taxonomy): ?array
    {
        return $this->buildTaxonomyHreflang(
            $taxonomy,
            fn ($site) => $this->collectionTaxonomyUrl($taxonomy, $site),
        );
    }

    protected function buildTaxonomyHreflang(Taxonomy $taxonomy, callable $resolveUrl): ?array
    {
        $initialSite = Site::current()->handle();

        if (! $this->isIndexableSite($initialSite)) {
            return null;
        }

        $sites = $taxonomy->sites()->filter($this->isIndexableSite(...));

        if ($sites->count() < 2) {
            return null;
        }

        $hreflang = $sites->map(fn ($site) => [
            'url' => $resolveUrl($site),
            'locale' => Helpers::parseLocale(Site::get($site)->locale()),
        ]);

        $originSite = $taxonomy->sites()->first();
        $xDefaultSite = $sites->contains($originSite) ? $originSite : $initialSite;

        return $hreflang->push([
            'url' => $resolveUrl($xDefaultSite),
            'locale' => 'x-default',
        ])->values()->all();
    }

    // TODO: Should be able to remove this once https://github.com/statamic/cms/pull/10439 is merged.
    protected function collectionTaxonomyUrl(Taxonomy $taxonomy, string $site): string
    {
        $siteUrl = Site::get($site)->absoluteUrl();
        $taxonomyHandle = $taxonomy->handle();
        $collectionHandle = $taxonomy->collection()->handle();

        return URL::tidy("{$siteUrl}/{$collectionHandle}/{$taxonomyHandle}");
    }

    protected function shouldIncludeHreflang(Entry|LocalizedTerm $model): bool
    {
        return ! $this->canonicalPointsToAnotherUrl($model)
            && $this->isIndexableEntryOrTerm($model);
    }

    protected function canonicalPointsToAnotherUrl(Entry|LocalizedTerm $model): bool
    {
        return $model->seo_canonical_type != 'current';
    }
}
