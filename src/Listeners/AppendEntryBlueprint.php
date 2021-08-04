<?php

namespace Aerni\AdvancedSeo\Listeners;

use Statamic\Events\EntryBlueprintFound;
use Facades\Aerni\AdvancedSeo\Blueprints\Sections\SeoEntrySection;

class AppendEntryBlueprint
{
    public function handle(EntryBlueprintFound $event): void
    {
        $blueprint = $event->blueprint->contents();
        $blueprint['sections']['seo'] = SeoEntrySection::contents();

        $event->blueprint->setContents($blueprint);
    }
}
