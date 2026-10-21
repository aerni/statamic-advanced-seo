<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Concerns\AsAction;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Site;

class ResolveXDefault
{
    use AsAction;

    /**
     * The x-default alternate.
     *
     * Entries, terms, and taxonomy indexes use the configured site, then their
     * original localization. A taxonomy index's original site is its first site.
     */
    public function handle(Collection $alternates, ?Taxonomy $taxonomy = null): Entry|Term|string|null
    {
        $first = $alternates->first();

        return match (true) {
            $first instanceof Entry, $first instanceof Term => $this->localization($alternates, $first),
            default => $this->forSites($alternates, $taxonomy),
        };
    }

    /**
     * Uses the configured site when that localization is among the alternates.
     * Otherwise uses the original localization when it is among the alternates.
     */
    protected function localization(Collection $localizations, Entry|Term $model): Entry|Term|null
    {
        $site = $this->site();

        if ($match = $localizations->first(fn (Entry|Term $localization) => $localization->locale() === $site)) {
            return $match;
        }

        $root = $this->root($model);

        return $localizations->first(fn (Entry|Term $localization) => $localization->locale() === $root->locale());
    }

    /**
     * Uses the configured site when it is among the alternates.
     * Otherwise uses the taxonomy's first site when it is among the alternates.
     */
    protected function forSites(Collection $sites, ?Taxonomy $taxonomy): ?string
    {
        $site = $this->site();

        if ($sites->contains($site)) {
            return $site;
        }

        $original = $taxonomy?->sites()->first();

        if (is_string($original) && $sites->contains($original)) {
            return $original;
        }

        return null;
    }

    protected function site(): string
    {
        $configured = Seo::find('site::defaults')?->config()->value('hreflang_x_default');

        if (is_string($configured) && Site::get($configured)) {
            return $configured;
        }

        return Site::default()->handle();
    }

    /**
     * Entries walk their localization origin. Terms have no root entry;
     * their original localization is the taxonomy's default locale.
     */
    protected function root(Entry|Term $model): Entry|Term
    {
        if (method_exists($model, 'root')) {
            return $model->root();
        }

        $origin = $model->hasOrigin() ? $model->origin() : null;

        return $origin ?? $model;
    }
}
