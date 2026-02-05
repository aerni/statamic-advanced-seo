<?php

namespace Aerni\AdvancedSeo\UpdateScripts;

use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Facades\Entry;
use Statamic\Facades\Taxonomy;
use Statamic\UpdateScripts\UpdateScript;

class RemoveTwitterTitleAndDescription extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion): bool
    {
        return $this->isUpdatingTo('3.0.0');
    }

    public function update(): void
    {
        $this->removeFromEntries();
        $this->removeFromTerms();
        $this->removeFromSeoSetLocalizations();

        $this->console()->info('Removed Twitter title and description fields.');
    }

    protected function removeFromEntries(): void
    {
        Entry::all()->each(function ($entry) {
            $entry->remove('seo_twitter_title')->remove('seo_twitter_description')->saveQuietly();
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
                            ->forget('seo_twitter_title')
                            ->forget('seo_twitter_description');
                    });

                    $term->save();
                });
        });
    }

    protected function removeFromSeoSetLocalizations(): void
    {
        Seo::all()
            ->filter(fn (SeoSet $set) => in_array($set->type(), ['collections', 'taxonomies']))
            ->each(function (SeoSet $set) {
                $set->localizations()->each(function ($localization) {
                    $localization->remove('seo_twitter_title')->remove('seo_twitter_description')->save();
                });
            });
    }
}
