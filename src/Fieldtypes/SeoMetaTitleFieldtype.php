<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Repositories\SiteDefaultsRepository;
use Illuminate\Support\Collection;
use Statamic\Facades\Site;
use Statamic\Fields\Fieldtype;

class SeoMetaTitleFieldtype extends Fieldtype
{
    protected $selectable = false;

    protected function fallbackSiteDefaults(): array
    {
        return [
            'site_name' => config('app.name'),
            'title_separator' => '|',
        ];
    }

    protected function siteDefaults(): Collection
    {
        $sites = Site::all()->map->handle();

        $repository = new SiteDefaultsRepository('general', $sites);

        $set = $repository->ensureLocalizations($sites)->set();

        $fallback = collect([
            'site_name' => config('app.name'),
            'title_separator' => '|',
        ]);

        $siteDefaults = $set->localizations()->map(function ($localization) use ($fallback) {
            return $fallback->merge(
                $localization->values()->only(['site_name', 'title_separator'])->all()
            )->all();
        });

        return $siteDefaults;
    }

    protected function contentDefaults(): Collection
    {
        $sites = Site::all()->map->handle();

        $sites = $this->field->parent()->sites();

        $contentDefaults = $sites->mapWithKeys(function ($site) {
            if ($set = Seo::find($this->type(), $this->typeHandle())) {
                return [$site => $set->in($site)->values()->only('seo_title')->all()];
            }
        });

        return $contentDefaults;
    }

    public function preload(): Collection
    {
        $defaults = $this->siteDefaults();

        // Load the localized content defaults if we're on an entry or term.
        if ($this->field->parent()) {
            $defaults = $defaults->mergeRecursive($this->contentDefaults());
        }

        return $defaults;
    }

    protected function type(): string
    {
        $parent = $this->field->parent();

        if ($parent instanceof \Statamic\Entries\Collection) {
            return 'collections';
        }

        if ($parent instanceof \Statamic\Entries\Entry) {
            return 'collections';
        }

        if ($parent instanceof \Statamic\Taxonomies\Taxonomy) {
            return 'taxonomies';
        }

        if ($parent instanceof \Statamic\Taxonomies\Term) {
            return 'taxonomies';
        }
    }

    protected function typeHandle(): string
    {
        $parent = $this->field->parent();

        if ($parent instanceof \Statamic\Entries\Collection) {
            return $parent->handle();
        }

        if ($parent instanceof \Statamic\Entries\Entry) {
            return $parent->collection()->handle();
        }

        if ($parent instanceof \Statamic\Taxonomies\Taxonomy) {
            return $parent->handle();
        }

        if ($parent instanceof \Statamic\Taxonomies\Term) {
            return $parent->taxonomy()->handle();
        }
    }
}
