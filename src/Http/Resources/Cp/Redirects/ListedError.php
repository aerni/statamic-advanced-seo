<?php

namespace Aerni\AdvancedSeo\Http\Resources\Cp\Redirects;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Redirects\ErrorHandledChecker;
use Illuminate\Http\Resources\Json\JsonResource;
use Statamic\Facades\Site;

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
            'destination' => $redirect?->destinationUrl() ?? $redirect?->destination(),
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
}
