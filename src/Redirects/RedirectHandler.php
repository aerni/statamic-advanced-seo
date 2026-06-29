<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Enums\RedirectType;
use Aerni\AdvancedSeo\Facades\Redirects;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Uri;
use Statamic\Facades\Site;
use Statamic\Statamic;

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

        $redirect = Redirects::resolve(
            path: $site->relativePath($request->url()),
            site: $site->handle()
        );

        if (! $redirect) {
            return;
        }

        if ($redirect->type === RedirectType::Gone) {
            abort(410);
        }

        if ($this->redirectsToItself($redirect->destination, $request)) {
            return;
        }

        $destination = $redirect->forwardQueryString
            ? $this->appendQueryString($redirect->destination, $request)
            : $redirect->destination;

        return redirect($destination, $redirect->type->value);
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
