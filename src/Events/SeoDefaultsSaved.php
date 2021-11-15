<?php

namespace Aerni\AdvancedSeo\Events;

use Aerni\AdvancedSeo\Data\SeoVariables;
use Statamic\Events\Event;

class SeoDefaultsSaved extends Event
{
    public function __construct(public SeoVariables $defaults)
    {
        //
    }
}
