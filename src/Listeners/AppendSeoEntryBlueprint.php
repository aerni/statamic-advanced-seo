<?php

namespace Aerni\AdvancedSeo\Listeners;

use Aerni\AdvancedSeo\Facades\Blueprint;
use Statamic\Events\EntryBlueprintFound;

class AppendSeoEntryBlueprint
{
    public function handle(EntryBlueprintFound $event): void
    {
        $blueprint = $event->blueprint->contents();
        $blueprint['sections']['seo'] = Blueprint::seoEntryContents()['sections']['seo'];

        $event->blueprint->setContents($blueprint);
    }
}
