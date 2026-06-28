<?php

namespace Aerni\AdvancedSeo\Http\Resources\Cp\Redirects;

use Illuminate\Http\Resources\Json\JsonResource;
use Statamic\Facades\Site;
use Statamic\Facades\User;

class ListedRedirect extends JsonResource
{
    public function toArray($request)
    {
        $redirect = $this->resource;

        return [
            'id' => $redirect->id(),
            'source' => $redirect->source(),
            'destination' => $redirect->destination(),
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
}
