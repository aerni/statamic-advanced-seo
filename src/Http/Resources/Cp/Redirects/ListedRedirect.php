<?php

namespace Aerni\AdvancedSeo\Http\Resources\Cp\Redirects;

use Illuminate\Http\Resources\Json\JsonResource;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Support\Str;

class ListedRedirect extends JsonResource
{
    public function toArray($request)
    {
        $redirect = $this->resource;

        $destinationIsEntry = Str::startsWith($redirect->destination() ?? '', 'entry::');

        $destinationUrl = $destinationIsEntry
            ? $this->entryDestinationUrl($redirect)
            : $redirect->destinationUrl();

        return [
            'id' => $redirect->id(),
            'source' => $redirect->source(),
            'destination' => $destinationUrl ?? $redirect->destination(),
            'destination_is_entry' => $destinationIsEntry,
            'type' => $redirect->type()->value,
            'type_label' => __('advanced-seo::fields.redirect_type.option_'.$redirect->type()->value),
            'site' => $redirect->site(),
            'site_name' => Site::get($redirect->site())?->name() ?? $redirect->site(),
            'status' => $redirect->enabled(),
            'edit_url' => $redirect->editUrl(),
            'delete_url' => $redirect->deleteUrl(),
            'editable' => User::current()->can('edit', $redirect),
            'deletable' => User::current()->can('delete', $redirect),
        ];
    }

    protected function entryDestinationUrl($redirect): ?string
    {
        $id = Str::after($redirect->destination(), 'entry::');

        return Entry::find($id)?->in($redirect->site())?->url();
    }
}
