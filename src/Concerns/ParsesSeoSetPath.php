<?php

namespace Aerni\AdvancedSeo\Concerns;

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

        return in_array($type, ['site', 'collections', 'taxonomies'])
            && ($type !== 'site' || $handle === 'defaults');
    }
}
