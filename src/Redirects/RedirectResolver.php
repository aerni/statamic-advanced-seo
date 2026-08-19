<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Enums\RedirectResponseCode;
use Aerni\AdvancedSeo\Enums\RedirectSourceType;
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

        if (! $redirect->resolves()) {
            return null;
        }

        if ($redirect->responseCode() === RedirectResponseCode::Gone) {
            return new ResolvedRedirect($redirect->id(), RedirectResponseCode::Gone, null);
        }

        $destination = $redirect->destinationUrl();

        if ($redirect->sourceType() !== RedirectSourceType::Exact) {
            $captures = RedirectPatternMatcher::match($redirect->source(), $this->path);
            $destination = RedirectPatternMatcher::substitute($destination, $captures);
        }

        return new ResolvedRedirect($redirect->id(), $redirect->responseCode(), $destination, $redirect->preserveQueryString());
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
            ->sortBy(fn (Redirect $redirect) => RedirectPatternMatcher::specificity(
                $redirect->source(),
                $redirect->sourceType() === RedirectSourceType::Regex,
            ))
            ->first(fn (Redirect $redirect) => RedirectPatternMatcher::match($redirect->source(), $this->path) !== null);
    }
}
