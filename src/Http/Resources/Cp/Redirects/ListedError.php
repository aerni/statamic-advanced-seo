<?php

namespace Aerni\AdvancedSeo\Http\Resources\Cp\Redirects;

use Aerni\AdvancedSeo\Enums\RedirectErrorStatus;
use Aerni\AdvancedSeo\Redirects\RedirectErrorMatcher;
use Illuminate\Http\Resources\Json\JsonResource;
use Statamic\Facades\Action;
use Statamic\Facades\Site;

class ListedError extends JsonResource
{
    protected ?RedirectErrorMatcher $matcher = null;

    public function matcher(RedirectErrorMatcher $matcher): static
    {
        $this->matcher = $matcher;

        return $this;
    }

    public function toArray($request)
    {
        $error = $this->resource;

        $redirect = $this->matcher->match($error->url(), $error->site());

        return [
            'id' => $error->id(),
            'url' => $error->url(),
            'hits' => $error->count(),
            'first_seen_at' => $error->firstSeenAtIso(),
            'last_seen_at' => $error->lastSeenAtIso(),
            'site' => $error->site(),
            'site_name' => Site::get($error->site())?->name() ?? $error->site(),
            'status' => RedirectErrorStatus::for($redirect)->value,
            'destination' => $redirect?->destinationUrl() ?? $redirect?->destination(),
            'response_code_label' => $redirect?->responseCode()->label(),
            'redirect_url' => $redirect?->editUrl(),
            'actions' => Action::for($error),
        ];
    }
}
