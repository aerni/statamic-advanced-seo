<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Statamic\Fields\Fieldtype;

class ComputedValueFieldtype extends Fieldtype
{
    protected $selectable = false;

    public function config(string $key = null, $fallback = null)
    {
        $config = collect([
            'antlers' => true,
        ]);

        return $key
            ? $config->get($key, $fallback)
            : $config->all();
    }
}
