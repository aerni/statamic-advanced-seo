<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Actions\ResolveSchema;
use Statamic\Fieldtypes\Code;

class JsonLdFieldtype extends Code
{
    protected $component = 'code';

    protected $selectable = false;

    public function augment($value)
    {
        $value = is_array($value) ? $value['code'] : $value;

        $parsed = ResolveSchema::handle($value, $this->field->parent(), $this->field->handle());

        return parent::augment($parsed);
    }
}
