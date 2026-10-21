<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Concerns\AsAction;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Site;

class ResolveXDefault
{
    use AsAction;

    /**
     * The x-default member of a cluster.
     *
     * Entries and terms use the configured site, then their original localization.
     * Taxonomy index sites use the configured site, then the first site.
     */
    public function handle(Collection $cluster): Entry|Term|string|null
    {
        $first = $cluster->first();

        return match (true) {
            $first instanceof Entry, $first instanceof Term => $this->localization($cluster, $first),
            default => $this->forSites($cluster),
        };
    }

    /**
     * Uses the configured site when that localization is in the cluster.
     * Otherwise uses the original localization when it is in the cluster.
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
     * Uses the configured site when it is one of the given sites.
     * Otherwise uses the first site.
     */
    protected function forSites(Collection $sites): ?string
    {
        $site = $this->site();

        if ($sites->contains($site)) {
            return $site;
        }

        return $sites->first();
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
