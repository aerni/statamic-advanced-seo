<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Enums\ResponseCode;
use Aerni\AdvancedSeo\Enums\SourceType;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Illuminate\Support\Collection;

class ErrorHandledChecker
{
    /**
     * @param  array<string, array<string, Redirect>>  $exact  site => [source => redirect]
     * @param  array<string, Collection<int, Redirect>>  $patterns  site => non-exact redirects
     */
    protected function __construct(
        protected array $exact,
        protected array $patterns,
    ) {}

    public static function for(array $sites): self
    {
        $redirects = RedirectFacade::query()
            ->whereIn('site', $sites)
            ->get();

        $exact = [];
        $patterns = [];

        foreach ($redirects as $redirect) {
            if ($redirect->sourceType() === SourceType::Exact) {
                $exact[$redirect->site()][$redirect->source()] = $redirect;
            } else {
                $patterns[$redirect->site()][] = $redirect;
            }
        }

        $patterns = array_map(
            fn ($group) => collect($group)->sortBy(fn (Redirect $redirect) => RedirectPatternMatcher::specificity(
                $redirect->source(),
                $redirect->sourceType() === SourceType::Regex,
            ))->values(),
            $patterns
        );

        return new self($exact, $patterns);
    }

    /**
     * Return the redirect that covers the given error, preferring an enabled
     * redirect over a disabled one, or null when nothing matches.
     */
    public function match(string $url, string $site): ?Redirect
    {
        return $this->matchIn($url, $site, enabled: true)
            ?? $this->matchIn($url, $site, enabled: false);
    }

    protected function matchIn(string $url, string $site, bool $enabled): ?Redirect
    {
        // An exact redirect shadows any pattern, mirroring the resolver.
        if (($exact = $this->exact[$site][$url] ?? null) && $exact->enabled() === $enabled) {
            return ! $enabled || $this->resolves($exact) ? $exact : null;
        }

        return ($this->patterns[$site] ?? collect())
            ->first(fn (Redirect $redirect) => $redirect->enabled() === $enabled
                && (! $enabled || $this->resolves($redirect))
                && RedirectPatternMatcher::match($redirect->source(), $url) !== null);
    }

    /**
     * Whether the redirect would actually produce a response, mirroring the
     * resolver: a Gone redirect always does, otherwise it needs a destination.
     */
    protected function resolves(Redirect $redirect): bool
    {
        return $redirect->responseCode() === ResponseCode::Gone
            || filled($redirect->destinationUrl());
    }
}
