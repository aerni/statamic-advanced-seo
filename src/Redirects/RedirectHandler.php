<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Enums\ResponseCode;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Aerni\AdvancedSeo\Jobs\RecordRedirectErrorJob;
use Aerni\AdvancedSeo\Jobs\RecordRedirectHitJob;
use Illuminate\Http\Request;
use Illuminate\Support\Uri;
use Statamic\Facades\Site;
use Statamic\Statamic;
use Statamic\Support\Str;

class RedirectHandler
{
    public function __invoke(Request $request)
    {
        if (! RedirectsFeature::enabled()) {
            return;
        }

        if (Statamic::isCpRoute() || Statamic::isApiRoute()) {
            return;
        }

        if (! in_array($request->getMethod(), ['GET', 'HEAD'])) {
            return;
        }

        $site = Site::current();

        $redirect = Redirect::resolve(
            path: $site->relativePath($request->url()),
            site: $site->handle()
        );

        if (! $redirect) {
            $this->recordError($request, $site);

            return;
        }

        if ($redirect->responseCode === ResponseCode::Gone) {
            $this->recordHit($redirect);

            abort(410);
        }

        if ($this->redirectsToItself($redirect->destination, $request)) {
            return;
        }

        $this->recordHit($redirect);

        $destination = $redirect->forwardQueryString
            ? $this->appendQueryString($redirect->destination, $request)
            : $redirect->destination;

        return redirect($destination, $redirect->responseCode->value);
    }

    protected function recordHit(ResolvedRedirect $redirect): void
    {
        if (! config('advanced-seo.redirects.hits.enabled')) {
            return;
        }

        RecordRedirectHitJob::dispatch($redirect->id);
    }

    protected function recordError(Request $request, $site): void
    {
        if (! config('advanced-seo.redirects.errors.enabled')) {
            return;
        }

        $url = Str::lower(RedirectPatternMatcher::normalizePath($site->relativePath($request->url())));

        $ignored = collect(config('advanced-seo.redirects.errors.ignore', []))
            ->contains(fn (string $pattern) => RedirectPatternMatcher::matches($pattern, $url));

        if ($ignored) {
            return;
        }

        RecordRedirectErrorJob::dispatch($url, $site->handle());
    }

    protected function redirectsToItself(string $destination, Request $request): bool
    {
        $destination = Uri::of($destination);

        $current = $request->uri();

        if ($destination->host() && $destination->host() !== $current->host()) {
            return false;
        }

        return $destination->path() === $current->path();
    }

    protected function appendQueryString(string $destination, Request $request): string
    {
        if (! $query = $request->getQueryString()) {
            return $destination;
        }

        [$destination, $fragment] = array_pad(explode('#', $destination, 2), 2, null);

        $separator = str_contains($destination, '?') ? '&' : '?';

        $destination = "{$destination}{$separator}{$query}";

        return $fragment === null ? $destination : "{$destination}#{$fragment}";
    }
}
