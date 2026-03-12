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
        return parent::augment(Token::parse($value, $this->field));
    }
}
