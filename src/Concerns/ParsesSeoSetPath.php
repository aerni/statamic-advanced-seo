<?php

namespace Aerni\AdvancedSeo\Concerns;

use Aerni\AdvancedSeo\Facades\Seo;

trait ParsesSeoSetPath
{
    protected function parseRelativePath(string $relativePath): array
    {
        $parts = explode('/', $relativePath);
        $handle = pathinfo($relativePath, PATHINFO_FILENAME);

        return ['type' => $parts[0], 'locale' => $parts[1] ?? null, 'handle' => $handle];
    }

    protected function isValidSeoSet(string $relativePath): bool
    {
        ['type' => $type, 'handle' => $handle] = $this->parseRelativePath($relativePath);

        return Seo::find("{$type}::{$handle}") !== null;
    }
}
