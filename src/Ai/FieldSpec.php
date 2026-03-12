<?php

namespace Aerni\AdvancedSeo\Ai;

readonly class FieldSpec
{
    public function __construct(
        public string $handle,
        public string $purpose,
        public int $characters,
    ) {}
}
