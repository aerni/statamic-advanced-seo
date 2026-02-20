<?php

namespace Aerni\AdvancedSeo\UpdateScripts\V3;

use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Taxonomy;

class MigrateSeoFields
{
    public function run(): void
    {
        $this->migrateTwitterCardToConfig();
        $this->migrateEntries();
        $this->migrateTerms();
        $this->migrateSeoSetLocalizations();
        $this->migrateSiteDefaults();
    }

    /**
     * Move seo_twitter_card from the default site's localization to the SeoSet config.
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
     * Remove twitter image fields from the site defaults.
     */
    protected function migrateSiteDefaults(): void
    {
        Seo::find('site::defaults')->localizations()->each(function ($localization) {
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
                } elseif (is_string($value) && str_contains($value, '@field:')) {
                    $item->set($key, preg_replace('/@field:([A-Za-z\d_-]+)/', '{{ $1 }}', $value));
                }
            });
    }

    protected function removeTwitterFields(mixed $item): void
    {
        $fields = ['seo_twitter_card', 'seo_twitter_title', 'seo_twitter_description', 'seo_twitter_summary_image', 'seo_twitter_summary_large_image'];

        foreach ($fields as $field) {
            if ($item instanceof Collection) {
                $item->forget($field);
            } else {
                $item->remove($field);
            }
        }
    }
}
