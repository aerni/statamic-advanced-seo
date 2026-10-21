<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Site;

class ResolveXDefault
{
    /**
     * The x-default localization for an entry or term cluster.
     *
     * Uses the configured site when that localization is in the cluster.
     * Otherwise uses the original localization when it is in the cluster.
     */
    public static function localization(Collection $localizations, Entry|Term $model): Entry|Term|null
    {
        $site = static::site();

        if ($match = $localizations->first(fn (Entry|Term $localization) => $localization->locale() === $site)) {
            return $match;
        }

        $root = static::root($model);

        return $localizations->first(fn (Entry|Term $localization) => $localization->locale() === $root->locale());
    }

    /**
     * The x-default site for a taxonomy index cluster.
     *
     * Uses the configured site when it is one of the given sites.
     * Otherwise uses the first site. Taxonomy indexes have no original localization.
     */
    public static function forSites(Collection $sites): ?string
    {
        $site = static::site();

        if ($sites->contains($site)) {
            return $site;
        }

        return $sites->first();
    }

    public static function site(): string
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
    protected static function root(Entry|Term $model): Entry|Term
    {
        if (method_exists($model, 'root')) {
            return $model->root();
        }

        $origin = $model->hasOrigin() ? $model->origin() : null;

        return $origin ?? $model;
    }
}
