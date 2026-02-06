<?php

namespace Aerni\AdvancedSeo\UpdateScripts;

use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Facades\Entry;
use Statamic\Facades\Taxonomy;
use Statamic\UpdateScripts\UpdateScript;

class MigrateTwitterFields extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion): bool
    {
        return $this->isUpdatingTo('3.0.0');
    }

    public function update(): void
    {
        $this->migrateTwitterCardToConfig();
        $this->removeFromEntries();
        $this->removeFromTerms();
        $this->removeFromSeoSetLocalizations();
        $this->removeFromSiteDefaults();

        $this->console()->info('Migrated Twitter fields.');
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

    protected function removeFromEntries(): void
    {
        Entry::all()->each(function ($entry) {
            $entry
                ->remove('seo_twitter_card')
                ->remove('seo_twitter_title')
                ->remove('seo_twitter_description')
                ->remove('seo_twitter_summary_image')
                ->remove('seo_twitter_summary_large_image')
                ->saveQuietly();
        });
    }

    protected function removeFromTerms(): void
    {
        Taxonomy::all()->each(function ($taxonomy) {
            $taxonomy->queryTerms()->get()
                ->map->term()
                ->unique()
                ->each(function ($term) {
                    $term->localizations()->each(function ($localization) {
                        $localization->data()
                            ->forget('seo_twitter_card')
                            ->forget('seo_twitter_title')
                            ->forget('seo_twitter_description')
                            ->forget('seo_twitter_summary_image')
                            ->forget('seo_twitter_summary_large_image');
                    });

                    $term->save();
                });
        });
    }

    /**
     * Remove twitter fields from collection and taxonomy SEO set localizations.
     */
    protected function removeFromSeoSetLocalizations(): void
    {
        Seo::all()
            ->filter(fn (SeoSet $set) => in_array($set->type(), ['collections', 'taxonomies']))
            ->each(function (SeoSet $set) {
                $set->localizations()->each(function ($localization) {
                    $localization
                        ->remove('seo_twitter_card')
                        ->remove('seo_twitter_title')
                        ->remove('seo_twitter_description')
                        ->remove('seo_twitter_summary_image')
                        ->remove('seo_twitter_summary_large_image')
                        ->save();
                });
            });
    }

    /**
     * Remove twitter image fields from the site-wide social media defaults.
     */
    protected function removeFromSiteDefaults(): void
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
}
