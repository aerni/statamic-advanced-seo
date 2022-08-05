<?php

namespace Aerni\AdvancedSeo\Updates;

use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Arr;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\Term;
use Statamic\UpdateScripts\UpdateScript;

class MigrateSiteNamePosition extends UpdateScript
{
    public function shouldUpdate($newVersion, $oldVersion)
    {
        return $this->isUpdatingTo('1.3.0');
    }

    public function update()
    {
        // The mapping of old and new values.
        $mapping = [
            'before' => 'end',
            'after' => 'start',
        ];

        // Get all localizations and make sure they exist.
        $siteDefaults = Seo::find('site', 'general')
            ?->ensureLocalizations(Site::all())
            ->localizations();

        // Get the title positions from each localization.
        $titlePositions = $siteDefaults
            ->map(fn ($data) => $data->get('title_position'))
            ->filter()
            ->map(fn ($value) => Arr::get($mapping, $value));

        // Set the site name position for all collection defaults.
        Seo::allOfType('collections')->each(function ($collection) use ($titlePositions) {
            $titlePositions->each(function ($value, $site) use ($collection) {
                $collection->in($site)
                    ?->set('seo_site_name_position', $value)
                    ->save();
            });
        });

        // Set the site name position for all taxonomy defaults.
        Seo::allOfType('taxonomies')->each(function ($taxonomy) use ($titlePositions) {
            $titlePositions->each(function ($value, $site) use ($taxonomy) {
                $taxonomy->in($site)
                    ?->set('seo_site_name_position', $value)
                    ->save();
            });
        });

        // Set the site name position on entries.
        Entry::query()
            ->whereNull('origin') // Only set on default site.
            ->whereNotNull('seo_title')
            ->get()
            ->each(fn ($entry) => $entry->set('seo_site_name_position', '@default')->saveQuietly());

        // Set the site name position on terms.
        Term::all()
            ->filter(fn ($term) => $term->has('seo_title'))
            ->each(fn ($term) => $term->set('seo_site_name_position', '@default')->save());

        // Remove the old title position from the site defaults.
        $siteDefaults
            ->filter(fn ($default) => $default->has('title_position')) // Prevent saving of defaults with no title position
            ->each(fn ($default) => $default->remove('title_position')->save());

        $this->console()->info("Successfully migrated 'title_position' to 'site_name_position'.");
    }
}
