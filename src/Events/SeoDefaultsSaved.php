<?php

namespace Aerni\AdvancedSeo\Events;

use Statamic\Events\Event;
use Aerni\AdvancedSeo\Data\SeoVariables;

class SeoDefaultsSaved extends Event
{
    public function __construct(public SeoVariables $defaults)
    {
        //
    }
}
