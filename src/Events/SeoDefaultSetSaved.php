<?php

namespace Aerni\AdvancedSeo\Events;

use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Statamic\Events\Event;

class SeoDefaultSetSaved extends Event
{
    public function __construct(public SeoDefaultSet $defaults)
    {
        //
    }
}
