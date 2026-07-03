<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Enums\ResponseCode;
use Aerni\AdvancedSeo\Enums\SourceType;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Statamic\Support\Str;

class RedirectResolver
{
    protected string $path;

    public function __construct(string $path, protected string $site)
    {
        $this->path = RedirectPatternMatcher::normalizePath($path);
    }

    public static function resolve(string $path, string $site): ?ResolvedRedirect
    {
        return (new self($path, $site))->process();
    }

    protected function process(): ?ResolvedRedirect
    {
        if (! $redirect = $this->findRedirect()) {
            return null;
        }

        if ($redirect->responseCode() === ResponseCode::Gone) {
            return new ResolvedRedirect($redirect->id(), ResponseCode::Gone, null);
        }

        if (! $destination = $redirect->destinationUrl()) {
            return null;
        }

        if ($redirect->sourceType() !== SourceType::Exact) {
            $captures = RedirectPatternMatcher::match($redirect->source(), $this->path);
            $destination = RedirectPatternMatcher::substitute($destination, $captures);
        }

        return new ResolvedRedirect($redirect->id(), $redirect->responseCode(), $destination, $redirect->forwardQueryString());
    }

    protected function findRedirect(): ?Redirect
    {
        return $this->findExactMatch() ?? $this->findPatternMatch();
    }

    protected function findExactMatch(): ?Redirect
    {
        return RedirectFacade::query()
            ->where('source', Str::lower($this->path))
            ->where('site', $this->site)
            ->where('enabled', true)
            ->first();
    }

    protected function findPatternMatch(): ?Redirect
    {
        return RedirectFacade::query()
            ->where('site', $this->site)
            ->where('enabled', true)
            ->get()
            ->sortBy(fn (Redirect $redirect) => $this->specificity($redirect))
            ->first(fn (Redirect $redirect) => RedirectPatternMatcher::match($redirect->source(), $this->path) !== null);
    }

    /**
     * Order pattern candidates most-specific first: wildcards before regex,
     * then fewer wildcards, then more literal characters.
     *
     * @return array{int, int, int}
     */
    protected function specificity(Redirect $redirect): array
    {
        $source = $redirect->source();
        $isRegex = $redirect->sourceType() === SourceType::Regex;

        $wildcards = $isRegex ? 0 : substr_count($source, '*');
        $literalLength = $isRegex ? strlen($source) : strlen(str_replace('*', '', $source));

        return [(int) $isRegex, $wildcards, -$literalLength];
    }
}
