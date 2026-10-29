<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Actions\ResolveXDefault;
use Aerni\AdvancedSeo\Actions\ViewExistsForSite;
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

        $sites = match (true) {
            $model instanceof Entry => $model->sites(),
            $model instanceof LocalizedTerm && $model->collection() => $model->taxonomy()->sites()->intersect($model->collection()->sites()),
            default => $model->taxonomy()->sites(),
        };

        if ($sites->count() < 2) {
            return null;
        }

        $localizations = $sites
            ->map(fn ($locale) => $model->in($locale))
            ->filter() // A model might not exist in a site. So we need to remove it to prevent calling methods on null
            ->filter($this->shouldIncludeHreflang(...))
            ->when($model instanceof LocalizedTerm, fn ($localizations) => $localizations
                ->filter(fn ($localization) => ViewExistsForSite::run($localization->template(), $localization->locale()))
            );

        if ($localizations->count() < 2) {
            return null;
        }

        return $localizations
            ->map(fn ($localization) => [
                'url' => $localization->absoluteUrl(),
                'locale' => Helpers::parseLocale($localization->site()->locale()),
            ])
            ->when(ResolveXDefault::run($localizations), fn ($hreflang, $xDefault) => $hreflang->push([
                'url' => $xDefault->absoluteUrl(),
                'locale' => 'x-default',
            ]))
            ->values()
            ->all();
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

        $sites = $taxonomy->sites()
            ->when($taxonomy->collection(), fn ($sites, $collection) => $sites->intersect($collection->sites()))
            ->filter($this->isIndexableSite(...))
            ->filter(fn ($site) => ViewExistsForSite::run($taxonomy->template(), $site));

        if ($sites->count() < 2) {
            return null;
        }

        return $sites
            ->map(fn ($site) => [
                'url' => $resolveUrl($site),
                'locale' => Helpers::parseLocale(Site::get($site)->locale()),
            ])
            ->when(ResolveXDefault::run($sites, $taxonomy), fn ($hreflang, $xDefaultSite) => $hreflang->push([
                'url' => $resolveUrl($xDefaultSite),
                'locale' => 'x-default',
            ]))
            ->values()
            ->all();
    }

    // TODO: Should be able to remove this once https://github.com/statamic/cms/pull/10439 is merged.
    protected function collectionTaxonomyUrl(Taxonomy $taxonomy, string $site): string
    {
        $siteUrl = Site::get($site)->absoluteUrl();
        $collection = $taxonomy->collection();
        $collectionUri = $collection->uri($site) ?? $collection->handle();
        $taxonomyHandle = str_replace('_', '-', $taxonomy->handle());

        return URL::assemble($siteUrl, $collectionUri, $taxonomyHandle);
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
