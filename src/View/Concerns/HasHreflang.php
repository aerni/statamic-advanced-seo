<?php

namespace Aerni\AdvancedSeo\View\Concerns;

use Aerni\AdvancedSeo\Concerns\EvaluatesIndexability;
use Aerni\AdvancedSeo\Support\Helpers;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\URL;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\Taxonomies\Taxonomy;

trait HasHreflang
{
    use EvaluatesIndexability;
    use HasAbsoluteUrl;

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
            'url' => $this->absoluteUrl($model),
            'locale' => Helpers::parseLocale($model->site()->locale()),
        ]);

        $origin = $model->origin() ?? $model;

        $xDefault = $this->shouldIncludeHreflang($origin) ? $origin : $model;

        return $hreflang->push([
            'url' => $this->absoluteUrl($xDefault),
            'locale' => 'x-default',
        ])->values()->all();
    }

    protected function taxonomyHreflang(Taxonomy $taxonomy): ?array
    {
        // Save initial site so that we can restore it later.
        $initialSite = Site::current()->handle();

        if (! $this->isIndexableSite($initialSite)) {
            return null;
        }

        $sites = $taxonomy
            ->sites()
            ->filter($this->isIndexableSite(...));

        if ($sites->count() < 2) {
            return null;
        }

        $hreflang = $sites->map(function ($site) use ($taxonomy) {
            // Set the site so we can get the localized absolute URLs of the taxonomy.
            Site::setCurrent($site);

            return [
                'url' => $taxonomy->absoluteUrl(),
                'locale' => Helpers::parseLocale(Site::current()->locale()),
            ];
        });

        $originSite = $taxonomy->sites()->first();

        $xDefaultSite = $sites->contains($originSite) ? $originSite : $initialSite;

        // Set the site so we can get the localized absolute URL for the x-default.
        Site::setCurrent($xDefaultSite);

        $hreflang->push([
            'url' => $taxonomy->absoluteUrl(),
            'locale' => 'x-default',
        ]);

        // Reset the site to the site of the model.
        Site::setCurrent($initialSite);

        return $hreflang->values()->all();
    }

    protected function collectionTaxonomyHreflang(Taxonomy $taxonomy): ?array
    {
        $initialSite = Site::current()->handle();

        if (! $this->isIndexableSite($initialSite)) {
            return null;
        }

        $sites = $taxonomy
            ->sites()
            ->filter($this->isIndexableSite(...));

        if ($sites->count() < 2) {
            return null;
        }

        $hreflang = $sites->map(fn ($site) => [
            'url' => $this->collectionTaxonomyUrl($taxonomy, $site),
            'locale' => Helpers::parseLocale(Site::get($site)->locale()),
        ]);

        $originSite = $taxonomy->sites()->first();

        $xDefaultSite = $sites->contains($originSite) ? $originSite : $initialSite;

        return $hreflang->push([
            'url' => $this->collectionTaxonomyUrl($taxonomy, $xDefaultSite),
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
        if ($this->canonicalPointsToAnotherUrl($model)) {
            return false;
        }

        return $this->isIndexable($model);
    }

    protected function canonicalPointsToAnotherUrl(Entry|LocalizedTerm $model): bool
    {
        return $model->seo_canonical_type != 'current';
    }
}
