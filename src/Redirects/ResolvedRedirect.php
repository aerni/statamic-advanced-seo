<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Enums\RedirectResponseCode;

class ResolvedRedirect
{
    public function __construct(
        public readonly string $id,
        public readonly RedirectResponseCode $responseCode,
        public readonly ?string $destination,
        public readonly bool $preserveQueryString = true,
    ) {}
}
