<?php

namespace Aerni\AdvancedSeo\Traits;

use Illuminate\Support\Arr;
use Statamic\Fields\Blueprint;
use Illuminate\Support\Collection;

trait GetsFieldsWithDefault
{
    use GetsContentDefaults;

    /**
     * Use the $getFresh argument if the blueprint fields don't return the localized values.
     */
    public function getFieldsWithDefault(Blueprint $blueprint, $getFresh = false): Collection
    {
        // Get the fields that have a default value set.
        $fieldsWithDefaults = $blueprint->fields()->resolveFields()->mapWithKeys(function ($field) {
            return [$field->handle() => Arr::get($field->config(), 'default')];
        })->filter(function ($value) {
            return $value !== null;
        });

        if (! $getFresh) {
            return $fieldsWithDefaults;
        }

        // Get the content defaults for the entry linked to the blueprint.
        $contentDefaults = collect($this->getContentDefaults($blueprint->getParent()))->map->raw();

        // Return the defaults for the current entry.
        return $contentDefaults->intersectByKeys($fieldsWithDefaults);
    }
}
