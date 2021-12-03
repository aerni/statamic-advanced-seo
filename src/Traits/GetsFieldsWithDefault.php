<?php

namespace Aerni\AdvancedSeo\Traits;

use Statamic\Fields\Field;

trait GetsFieldsWithDefault
{
    public function getFieldsWithDefault(array $fields)
    {
        return collect($fields)->mapWithKeys(function ($config, $handle) {
            $field = new Field($handle, $config);

            return [$field->handle() => $field->preProcess()->value()];
        })->filter();
    }
}
