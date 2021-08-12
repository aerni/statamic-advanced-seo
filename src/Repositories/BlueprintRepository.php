<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Contracts\BlueprintRepository as Contract;
use Facades\Aerni\AdvancedSeo\Blueprints\SeoEntryBlueprint;
use Facades\Aerni\AdvancedSeo\Blueprints\SeoGlobalsBlueprint;

class BlueprintRepository implements Contract
{
    protected mixed $data;

    public function for(mixed $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function contents(): ?array
    {
        if ($this->data instanceof \Statamic\Entries\Entry) {
            return SeoEntryBlueprint::for($this->data)->contents();
        }

        if ($this->data instanceof \Statamic\Globals\Variables) {
            return SeoGlobalsBlueprint::for($this->data)->contents();
        }

        return null;
    }
}
