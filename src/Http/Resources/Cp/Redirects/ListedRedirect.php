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
            'destination' => $this->destinationDisplay($redirect, $destinationIsEntry),
            'destination_url' => $this->destinationAbsoluteUrl($redirect, $destinationIsEntry),
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

    protected function destinationDisplay($redirect, bool $destinationIsEntry): ?string
    {
        if ($destinationIsEntry) {
            $id = Str::after($redirect->destination(), 'entry::');
            $entry = Entry::find($id)?->in($redirect->site());

            return $entry?->url() ?? $redirect->destination();
        }

        return $redirect->destination();
    }

    protected function destinationAbsoluteUrl($redirect, bool $destinationIsEntry): ?string
    {
        if ($destinationIsEntry) {
            $id = Str::after($redirect->destination(), 'entry::');

            return Entry::find($id)?->in($redirect->site())?->absoluteUrl();
        }

        $destination = $redirect->destination();

        if (is_null($destination)) {
            return null;
        }

        if (Str::startsWith($destination, ['http://', 'https://'])) {
            return $destination;
        }

        $siteAbsoluteUrl = Str::removeRight(Site::get($redirect->site())->absoluteUrl(), '/');

        return $siteAbsoluteUrl.$destination;
    }
}
