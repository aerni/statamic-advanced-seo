<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Facades\SeoGlobals;
use Statamic\Events\GlobalVariablesBlueprintFound;

class AppendSeoGlobalsBlueprint
{
    public function handle(GlobalVariablesBlueprintFound $event): void
    {
        if ($event->globals->id() === SeoGlobals::handle()) {
            $event->blueprint->setContents(SeoGlobals::blueprint());
        }
    }
}
