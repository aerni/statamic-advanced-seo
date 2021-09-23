<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Facades\Site;
use Statamic\Fields\Fieldtype;

class SeoMetaTitleFieldtype extends Fieldtype
{
    protected $selectable = false;

    public function preload()
    {
        // Load the localized site defaults.
        $defaults = Site::all()->map(function ($site) {
            return Seo::find('site', 'general')
                ->in($site->handle())
                ->values()
                ->only(['site_name', 'title_separator'])
                ->all();
        });

        // Load the localized content defaults if we're on an entry.
        if ($this->field->parent()) {
            $contentDefaults = Site::all()->map(function ($site) {
                return Seo::find($this->type(), $this->typeHandle())
                    ->in($site->handle())
                    ->values()
                    ->only('seo_title')
                    ->all();
            });

            $defaults = $defaults->mergeRecursive($contentDefaults);
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
