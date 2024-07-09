<?php

namespace Aerni\AdvancedSeo\View\Concerns;

use Statamic\Facades\URL;
use Statamic\Facades\Site;
use Statamic\Taxonomies\Taxonomy;
use Statamic\Contracts\Entries\Entry;
use Aerni\AdvancedSeo\Support\Helpers;
use Statamic\Taxonomies\LocalizedTerm;
use Aerni\AdvancedSeo\View\Concerns\EvaluatesIndexability;

trait HasHreflang
{
    use EvaluatesIndexability;
    use HasAbsoluteUrl;

    protected function entryAndTermHreflang(Entry|LocalizedTerm $model): ?array
    {
        if (! $this->isIndexable($model)) {
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
            ->filter($this->isIndexable(...));

        if ($hreflang->count() < 2) {
            return null;
        }

        $hreflang->transform(fn ($model) => [
            'url' => $this->absoluteUrl($model),
            'locale' => Helpers::parseLocale($model->site()->locale()),
        ]);

        $origin = $model->origin() ?? $model;

        $xDefault = $this->isIndexable($origin) ? $origin : $model;

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

    protected function collectionTermHreflang(LocalizedTerm $term): ?array
    {
        if (! $this->isIndexable($term)) {
            return null;
        }

        $sites = $term->taxonomy()->sites();

        if ($sites->count() < 2) {
            return null;
        }

        $hreflang = $sites
            ->map(fn ($locale) => $term->in($locale))
            ->filter($this->isIndexable(...));

        if ($hreflang->count() < 2) {
            return null;
        }

        $hreflang->transform(fn ($term) => [
            'url' => $term->absoluteUrl(),
            'locale' => Helpers::parseLocale($term->site()->locale()),
        ]);

        $origin = $term->origin();

        $xDefault = $this->isIndexable($origin) ? $origin : $term;

        return $hreflang->push([
            'url' => $xDefault->absoluteUrl(),
            'locale' => 'x-default',
        ])->values()->all();
    }

    protected function collectionTaxonomyUrl(Taxonomy $taxonomy, string $site): string
    {
        $siteUrl = Site::get($site)->absoluteUrl();
        $taxonomyHandle = $taxonomy->handle();
        $collectionHandle = $taxonomy->collection()->handle();

        return URL::tidy("{$siteUrl}/{$collectionHandle}/{$taxonomyHandle}");
    }
}
