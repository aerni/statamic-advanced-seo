<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Actions\ResolveSchema;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Fieldtypes\Code;

class JsonLdFieldtype extends Code
{
    protected $component = 'code';

    protected $selectable = false;

    /**
     * Whether a schema is currently being augmented, used to bail on re-entry
     * (e.g. a schema referencing its own field) and prevent infinite recursion.
     */
    protected static bool $augmenting = false;

    public function augment($value)
    {
        $value = is_array($value) ? $value['code'] : $value;

        if (static::$augmenting) {
            return parent::augment(null);
        }

        static::$augmenting = true;

        try {
            $parent = $this->field->parent();

            return parent::augment(match (true) {
                $parent instanceof Entry,
                $parent instanceof Term => ResolveSchema::handle($value, $parent),
                default => $value,
            });
        } finally {
            static::$augmenting = false;
        }
    }
}
