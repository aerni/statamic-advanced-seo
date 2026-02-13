<?php

namespace Aerni\AdvancedSeo\UpdateScripts;

use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Taxonomy;
use Statamic\UpdateScripts\UpdateScript;

class MigrateSeoFields extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion): bool
    {
        return $this->isUpdatingTo('3.0.0');
    }

    public function update(): void
    {
        $this->migrateTwitterCardToConfig();
        $this->migrateEntries();
        $this->migrateTerms();
        $this->migrateSeoSetLocalizations();
        $this->migrateSiteDefaults();

        $this->console()->info('Migrated legacy seo field values.');
    }

    /**
     * Move seo_twitter_card from localizations to the SeoSet config as twitter_card.
     *
     * The twitter card type was previously a per-localization setting. It is now a
     * per-collection/taxonomy config setting. We take the value from the default
     * site's localization and store it on the config. If no value was explicitly
     * set, we skip the config update and let the blueprint default handle it.
     */
    protected function migrateTwitterCardToConfig(): void
    {
        Seo::all()
            ->filter(fn (SeoSet $set) => in_array($set->type(), ['collections', 'taxonomies']))
            ->each(function (SeoSet $set) {
                if ($value = $set->inDefaultSite()->get('seo_twitter_card')) {
                    $set->config()->set('twitter_card', $value)->save();
                }
            });
    }

    protected function migrateEntries(): void
    {
        Entry::all()->each(function ($entry) {
            $this->migrateLegacyValues($entry);
            $this->removeTwitterFields($entry);
            $entry->saveQuietly();
        });
    }

    protected function migrateTerms(): void
    {
        Taxonomy::all()->each(function ($taxonomy) {
            $taxonomy->queryTerms()->get()
                ->map->term()
                ->unique()
                ->each(function ($term) {
                    $term->localizations()->each(function ($localization) {
                        $this->migrateLegacyValues($localization);
                        $this->removeTwitterFields($localization->data());
                    });

                    $term->saveQuietly();
                });
        });
    }

    protected function migrateSeoSetLocalizations(): void
    {
        Seo::all()
            ->filter(fn (SeoSet $set) => in_array($set->type(), ['collections', 'taxonomies']))
            ->each(function (SeoSet $set) {
                $set->localizations()->each(function ($localization) {
                    $this->migrateLegacyValues($localization);
                    $this->removeTwitterFields($localization);
                    $localization->save();
                });
            });
    }

    /**
     * Remove twitter image fields from the site-wide social media defaults.
     */
    protected function migrateSiteDefaults(): void
    {
        $set = Seo::all()->first(fn (SeoSet $set) => $set->id() === 'site::social_media');

        if (! $set) {
            return;
        }

        $set->localizations()->each(function ($localization) {
            $localization
                ->remove('twitter_summary_image')
                ->remove('twitter_summary_large_image')
                ->save();
        });
    }

    protected function migrateLegacyValues(mixed $item): void
    {
        collect($item->data())
            ->filter(fn ($value, $key) => str_starts_with($key, 'seo_'))
            ->each(function ($value, $key) use ($item) {
                if ($value === '@auto' || $value === '@null') {
                    $item->set($key, '@default');
                }

                if (is_string($value) && str_contains($value, '@field:')) {
                    $item->set($key, preg_replace('/@field:([A-Za-z\d_-]+)/', '{{ $1 }}', $value));
                }
            });
    }

    protected function removeTwitterFields(mixed $item): void
    {
        $fields = ['seo_twitter_card', 'seo_twitter_title', 'seo_twitter_description', 'seo_twitter_summary_image', 'seo_twitter_summary_large_image'];

        foreach ($fields as $field) {
            $item instanceof Collection ? $item->forget($field) : $item->remove($field);
        }
    }
}
