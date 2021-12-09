<?php

namespace Aerni\AdvancedSeo\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Statamic\Fields\Blueprint;

trait GetsFieldDefaults
{
    use GetsContentDefaults;

    /**
     * Use the $localized parameter to automatically get the field defaults by the blueprint parent's locale.
     * Use the $locale parameter to get the defaults of a specific locale.
     */
    public function getFieldDefaults(Blueprint $blueprint, $localized = false, string $locale = null): Collection
    {
        // Get the fields that have a default value set.
        $fieldsWithDefaults = $blueprint->fields()->resolveFields()->mapWithKeys(function ($field) {
            return [$field->handle() => Arr::get($field->config(), 'default')];
        })->filter(function ($value) {
            return $value !== null;
        });

        if (! $localized) {
            return $fieldsWithDefaults;
        }

        // Get the content defaults for the entry linked to the blueprint.
        $contentDefaults = collect($this->getContentDefaults($blueprint->parent(), $locale))->map->raw();

        // Return the defaults for the current entry.
        return $contentDefaults->intersectByKeys($fieldsWithDefaults);
    }
}
