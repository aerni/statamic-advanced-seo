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
     * The x-default for a cluster.
     *
     * Pass an entry or term to resolve its localization. Pass only site handles
     * for a taxonomy index, which has no original localization.
     */
    public function handle(Collection $cluster, Entry|Term|null $model = null): Entry|Term|string|null
    {
        if ($model) {
            return $this->localization($cluster, $model);
        }

        return $this->forSites($cluster);
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
