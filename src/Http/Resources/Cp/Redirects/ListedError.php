<?php

namespace Aerni\AdvancedSeo\Http\Resources\Cp\Redirects;

use Aerni\AdvancedSeo\Redirects\ErrorHandledChecker;
use Illuminate\Http\Resources\Json\JsonResource;
use Statamic\Facades\Action;
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
            'status' => ErrorHandledChecker::status($redirect),
            'destination' => $redirect?->destinationUrl() ?? $redirect?->destination(),
            'response_code_label' => $redirect?->responseCode()->label(),
            'redirect_url' => $redirect?->editUrl(),
            'actions' => Action::for($error),
        ];
    }
}
