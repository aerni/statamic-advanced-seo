<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Enums\RedirectType;

class ResolvedRedirect
{
    public function __construct(
        public readonly RedirectType $type,
        public readonly ?string $destination,
        public readonly bool $forwardQueryString = true,
    ) {}
}
