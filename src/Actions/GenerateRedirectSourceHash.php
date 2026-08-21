<?php

namespace Aerni\AdvancedSeo\Actions;

class GenerateRedirectSourceHash
{
    public static function handle(string $source): string
    {
        return hash('xxh128', $source);
    }
}
