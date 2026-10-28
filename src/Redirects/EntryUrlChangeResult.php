<?php

namespace Aerni\AdvancedSeo\Redirects;

readonly class EntryUrlChangeResult
{
    public function __construct(
        public ?string $oldPath,
        public ?string $currentPath,
    ) {}

    public function moved(): bool
    {
        return $this->oldPath !== null
            && $this->currentPath !== null
            && $this->oldPath !== $this->currentPath;
    }
}
