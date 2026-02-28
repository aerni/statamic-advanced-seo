<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Support\AntlersParser;
use Statamic\Fieldtypes\Code;

class JsonLdFieldtype extends Code
{
    protected static $handle = 'json_ld';

    protected $selectable = false;

    protected $component = 'code';

    public function augment($value)
    {
        return parent::augment(AntlersParser::parse($value, $this->field));
    }
}
