<?php

namespace Aerni\AdvancedSeo\UpdateScripts;

use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Statamic\UpdateScripts\UpdateScript;

class MigrateSitemapsConfig extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion): bool
    {
        return $this->isUpdatingTo('3.0.0');
    }

    public function update(): void
    {
        $indexingSet = Seo::find('site::indexing');

        $excludedCollections = $this->buildExclusionMap($indexingSet, 'excluded_collections');
        $excludedTaxonomies = $this->buildExclusionMap($indexingSet, 'excluded_taxonomies');

        $this->migrateType('collections', $excludedCollections);
        $this->migrateType('taxonomies', $excludedTaxonomies);

        $indexingSet->localizations()->each(function ($localization) {
            $localization->remove('excluded_collections');
            $localization->remove('excluded_taxonomies');
        });

        $indexingSet->save();

        $this->console()->info('Migrated sitemap configuration from site::indexing to individual collection and taxonomy configs.');
    }

    /**
     * Creates a map of localizations excluded from the sitemap keyed by the handle of the collection/taxonomy.
     * [
     *     'pages' => ['default', 'german'],
     *     'tags' => ['french'],
     * ]
     */
    protected function buildExclusionMap(SeoSet $set, string $field): Collection
    {
        return $set
            ->localizations()
            ->map(fn ($localization, $site) => $localization->value($field))
            ->filter()
            ->map(fn ($handles, $site) => ['handles' => $handles])
            ->groupBy('handles', true)
            ->map(fn ($sites) => $sites->keys());
    }

    protected function migrateType(string $type, Collection $handles): void
    {
        $handles
            ->map(fn ($sites, $handle) => Seo::find("{$type}::{$handle}"))
            ->filter()
            ->each(function ($set, $handle) use ($handles) {
                $localizationsWithDisabledSitemap = $handles[$handle];
                $setSites = $set->sites()->keys();
                $localizationsWithEnabledSitemap = $setSites->diff($localizationsWithDisabledSitemap);

                if ($localizationsWithEnabledSitemap->isEmpty()) {
                    $set->config()->set('sitemap', false)->save();

                    return;
                }

                $set->config()->set('sitemap', true);

                $localizationsWithDisabledSitemap->each(fn ($site) => $set->in($site)->set('seo_sitemap_enabled', false));

                $set->save();
            });
    }
}
