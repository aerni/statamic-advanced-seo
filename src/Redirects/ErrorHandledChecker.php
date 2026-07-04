<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Enums\SourceType;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Illuminate\Support\Collection;

class ErrorHandledChecker
{
    /**
     * @param  array<string, array<string, true>>  $exact  site => [lowercased source => true]
     * @param  array<string, Collection<int, Redirect>>  $patterns  site => enabled non-exact redirects
     */
    protected function __construct(
        protected array $exact,
        protected array $patterns,
    ) {}

    public static function for(array $sites): self
    {
        $redirects = RedirectFacade::query()
            ->whereIn('site', $sites)
            ->where('enabled', true)
            ->get();

        $exact = [];
        $patterns = [];

        foreach ($redirects as $redirect) {
            if ($redirect->sourceType() === SourceType::Exact) {
                $exact[$redirect->site()][$redirect->source()] = true;
            } else {
                $patterns[$redirect->site()][] = $redirect;
            }
        }

        return new self($exact, array_map(fn ($group) => collect($group), $patterns));
    }

    public function isHandled(string $url, string $site): bool
    {
        if (isset($this->exact[$site][$url])) {
            return true;
        }

        return ($this->patterns[$site] ?? collect())
            ->contains(fn (Redirect $redirect) => RedirectPatternMatcher::match($redirect->source(), $url) !== null);
    }
}
