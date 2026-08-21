<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Actions\GenerateRedirectSourceHash;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;

trait QueriesRedirectSources
{
    public function whereSource(string $source): static
    {
        $source = RedirectFacade::make()->source($source)->source();

        return $this
            ->where('source_hash', GenerateRedirectSourceHash::handle($source))
            ->where('source', $source);
    }
}
