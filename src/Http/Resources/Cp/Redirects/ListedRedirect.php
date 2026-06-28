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

        return [
            'id' => $redirect->id(),
            'source' => $redirect->source(),
            'match_type' => $redirect->matchType()->value,
            'match_type_label' => $redirect->matchType()->label(),
            'destination' => $this->destinationDisplay($redirect, $destinationIsEntry),
            'destination_url' => $redirect->destinationUrl(),
            'destination_is_entry' => $destinationIsEntry,
            'type' => $redirect->type()->value,
            'type_label' => $redirect->type()->label(),
            'site' => $redirect->site(),
            'site_name' => Site::get($redirect->site())?->name() ?? $redirect->site(),
            'status' => $redirect->enabled(),
            'edit_url' => $redirect->editUrl(),
            'delete_url' => $redirect->deleteUrl(),
            'editable' => User::current()->can('edit', $redirect),
            'deletable' => User::current()->can('delete', $redirect),
        ];
    }

    protected function destinationDisplay($redirect, bool $destinationIsEntry): ?string
    {
        if ($destinationIsEntry) {
            $id = Str::after($redirect->destination(), 'entry::');
            $entry = Entry::find($id)?->in($redirect->site());

            return $entry?->url() ?? $redirect->destination();
        }

        $destination = $redirect->destination();

        if ($destination !== null && ! Str::startsWith($destination, ['http://', 'https://'])) {
            return Str::ensureLeft($destination, '/');
        }

        return $destination;
    }
}
