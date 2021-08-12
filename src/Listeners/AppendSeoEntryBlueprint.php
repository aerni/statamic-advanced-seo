<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Facades\Blueprint;
use Statamic\Events\EntryBlueprintFound;

class AppendSeoEntryBlueprint
{
    public function handle(EntryBlueprintFound $event): void
    {
        $blueprint = $event->blueprint->contents();
        $seoBlueprint = Blueprint::for($event->entry)->contents();

        if ($seoBlueprint) {
            $blueprint['sections']['seo'] = $seoBlueprint['sections']['seo'];
            $event->blueprint->setContents($blueprint);
        }
    }
}
