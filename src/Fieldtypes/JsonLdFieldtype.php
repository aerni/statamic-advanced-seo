<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Facades\Token;
use Statamic\Fieldtypes\Code;

class JsonLdFieldtype extends Code
{
    protected static $handle = 'json_ld';

    protected $selectable = false;

    protected $component = 'code';

    public function augment($value)
    {
        $value = is_array($value) ? $value['code'] : $value;

        $parsed = Token::parse($value, $this->field);

        return parent::augment($parsed);
    }
}
