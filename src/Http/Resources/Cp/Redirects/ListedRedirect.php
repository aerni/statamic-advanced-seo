<?php

namespace Aerni\AdvancedSeo\Http\Resources\Cp\Redirects;

use Aerni\AdvancedSeo\Enums\RedirectType;
use Illuminate\Http\Resources\Json\JsonResource;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Support\Str;

class ListedRedirect extends JsonResource
{
    public function toArray($request)
    {
        $redirect = $this->resource;

        $destination = $redirect->destination();
        $destinationUrl = $redirect->destinationUrl();

        return [
            'id' => $redirect->id(),
            'source' => $redirect->source(),
            'destination' => $destinationUrl ?? $destination,
            'destination_url' => $destinationUrl,
            'destination_is_entry' => Str::startsWith($destination ?? '', 'entry::'),
            'type' => $redirect->type()->value,
            'type_label' => $redirect->type()->label(),
            'forward_query_string' => $redirect->type() === RedirectType::Gone ? null : $redirect->forwardQueryString(),
            'automatic' => $redirect->automatic(),
            'description' => $redirect->description(),
            'site' => $redirect->site(),
            'site_name' => Site::get($redirect->site())?->name() ?? $redirect->site(),
            'status' => $redirect->enabled(),
            'edit_url' => $redirect->editUrl(),
            'test_url' => $redirect->type() === RedirectType::Gone ? null : $redirect->sourceUrl(),
            'editable' => User::current()->can('edit', $redirect),
            'deletable' => User::current()->can('delete', $redirect),
        ];
    }
}
