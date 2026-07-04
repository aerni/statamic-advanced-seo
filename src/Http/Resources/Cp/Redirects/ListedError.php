<?php

namespace Aerni\AdvancedSeo\Http\Resources\Cp\Redirects;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Redirects\ErrorHandledChecker;
use Illuminate\Http\Resources\Json\JsonResource;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Support\Str;

class ListedError extends JsonResource
{
    protected ?ErrorHandledChecker $handledChecker = null;

    public function handledChecker(ErrorHandledChecker $checker): static
    {
        $this->handledChecker = $checker;

        return $this;
    }

    public function toArray($request)
    {
        $error = $this->resource;

        $redirect = $this->handledChecker->match($error->url(), $error->site());

        return [
            'id' => $error->id(),
            'url' => $error->url(),
            'hits' => $error->count(),
            'first_seen_at' => $error->firstSeenAtIso(),
            'last_seen_at' => $error->lastSeenAtIso(),
            'site' => $error->site(),
            'site_name' => Site::get($error->site())?->name() ?? $error->site(),
            'status' => $this->status($redirect),
            'destination' => $this->destination($redirect),
            'redirect_url' => $redirect?->editUrl(),
            'create_redirect_url' => cp_route('advanced-seo.redirects.create').'?source='.urlencode($error->url()).'&site='.$error->site(),
        ];
    }

    protected function status(?Redirect $redirect): string
    {
        if ($redirect === null) {
            return 'unhandled';
        }

        return $redirect->enabled() ? 'handled' : 'disabled';
    }

    /**
     * Resolve an entry destination to its URL, but leave relative paths and
     * full URLs as they were entered. Entries in the redirect's own site use
     * a relative URI; entries in another site use the absolute URL.
     */
    protected function destination(?Redirect $redirect): ?string
    {
        if (($destination = $redirect?->destination()) === null) {
            return null;
        }

        if (! Str::startsWith($destination, 'entry::')) {
            return $destination;
        }

        if (! $entry = Entry::find(Str::after($destination, 'entry::'))) {
            return $destination;
        }

        if (! $url = $entry->absoluteUrl()) {
            return $destination;
        }

        return $entry->locale() === $redirect->site()
            ? Site::get($redirect->site())->relativePath($url)
            : $url;
    }
}
