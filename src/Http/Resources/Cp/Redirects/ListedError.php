<?php

namespace Aerni\AdvancedSeo\Http\Resources\Cp\Redirects;

use Illuminate\Http\Resources\Json\JsonResource;
use Statamic\Facades\Site;

class ListedError extends JsonResource
{
    protected $handledChecker;

    public function handledChecker($checker): static
    {
        $this->handledChecker = $checker;

        return $this;
    }

    public function toArray($request)
    {
        $error = $this->resource;

        return [
            'id' => $error->id(),
            'url' => $error->url(),
            'hits' => $error->count(),
            'last_seen_at' => $error->lastSeenAtIso(),
            'site' => $error->site(),
            'site_name' => Site::get($error->site())?->name() ?? $error->site(),
            'handled' => $this->handledChecker->isHandled($error->url(), $error->site()),
            'create_redirect_url' => cp_route('advanced-seo.redirects.create').'?source='.urlencode($error->url()).'&site='.$error->site(),
        ];
    }
}
