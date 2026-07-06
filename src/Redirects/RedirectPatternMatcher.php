<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Enums\SourceType;
use Statamic\Support\Str;

class RedirectPatternMatcher
{
    public static function match(string $source, string $path): ?array
    {
        $pattern = match (SourceType::fromSource($source)) {
            SourceType::Regex => $source,
            SourceType::Wildcard => static::wildcardToRegex($source),
            SourceType::Exact => null,
        };

        return $pattern && @preg_match($pattern, $path, $matches) ? $matches : null;
    }

    public static function matches(string $source, string $path): bool
    {
        if (SourceType::fromSource($source) === SourceType::Exact) {
            return Str::lower(static::normalizePath($source)) === Str::lower(static::normalizePath($path));
        }

        return static::match($source, $path) !== null;
    }

    public static function substitute(string $destination, array $captures): string
    {
        return preg_replace_callback('/\$(\d+)/', fn ($m) => $captures[(int) $m[1]] ?? '', $destination);
    }

    /**
     * Order pattern candidates most-specific first: wildcards before regex,
     * then fewer wildcards, then more literal characters.
     *
     * @return array{int, int, int}
     */
    public static function specificity(string $source, bool $isRegex): array
    {
        $wildcards = $isRegex ? 0 : substr_count($source, '*');
        $literalLength = $isRegex ? strlen($source) : strlen(str_replace('*', '', $source));

        return [(int) $isRegex, $wildcards, -$literalLength];
    }

    public static function normalizePath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?? $path;

        return '/'.trim($path, '/');
    }

    protected static function wildcardToRegex(string $source): string
    {
        $quoted = preg_quote(static::normalizePath($source), '#');
        $regex = Str::replace('\*', '([^/]+)', $quoted);

        return "#^{$regex}$#i";
    }
}
