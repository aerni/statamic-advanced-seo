<?php

namespace Aerni\AdvancedSeo\Http\Resources\Cp\Redirects;

use Aerni\AdvancedSeo\Enums\ResponseCode;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Support\Str;

class ListedRedirect extends JsonResource
{
    protected ?Collection $hits = null;

    public function hits(?Collection $hits): static
    {
        $this->hits = $hits;

        return $this;
    }

    public function toArray($request)
    {
        $redirect = $this->resource;

        $destination = $redirect->destination();
        $destinationUrl = $redirect->destinationUrl();

        $hit = $this->hits?->get($redirect->id());

        return [
            'id' => $redirect->id(),
            'source' => $redirect->source(),
            'destination' => $destinationUrl ?? $destination,
            'destination_url' => $destinationUrl,
            'destination_is_entry' => Str::startsWith($destination ?? '', 'entry::'),
            'response_code' => $redirect->responseCode()->value,
            'response_code_label' => $redirect->responseCode()->label(),
            'preserve_query_string' => $redirect->responseCode() === ResponseCode::Gone ? null : $redirect->preserveQueryString(),
            'automatic' => $redirect->automatic(),
            'description' => $redirect->description(),
            'site' => $redirect->site(),
            'site_name' => Site::get($redirect->site())?->name() ?? $redirect->site(),
            'status' => $redirect->enabled(),
            'hits' => $hit?->count() ?? 0,
            'last_hit_at' => $hit?->lastHitAtIso(),
            'created_at' => $redirect->createdAtIso(),
            'edit_url' => $redirect->editUrl(),
            'test_url' => $redirect->responseCode() === ResponseCode::Gone ? null : $redirect->sourceUrl(),
            'editable' => User::current()->can('edit', $redirect),
            'deletable' => User::current()->can('delete', $redirect),
        ];
    }
}
