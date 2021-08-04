<?php

namespace Aerni\AdvancedSeo\Listeners;

use Facades\Aerni\AdvancedSeo\Blueprints\Sections\SeoEntrySection;
use Statamic\Events\EntryBlueprintFound;

class AppendEntryBlueprint
{
    public function handle(EntryBlueprintFound $event): void
    {
        $blueprint = $event->blueprint->contents();
        $blueprint['sections']['seo'] = SeoEntrySection::contents();

        $event->blueprint->setContents($blueprint);
    }
}
