<?php

namespace Aerni\AdvancedSeo\Http\Resources\Cp\Redirects;

use Illuminate\Http\Resources\Json\JsonResource;
use Statamic\Facades\User;

class ListedRedirect extends JsonResource
{
    protected $columns;

    public function columns($columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    public function toArray($request)
    {
        $redirect = $this->resource;

        return [
            'id' => $redirect->id(),
            'source' => $redirect->source(),
            'destination' => $redirect->destination(),
            'type' => $redirect->type()->value,
            'type_label' => __('advanced-seo::fields.redirect_type.option_'.$redirect->type()->value),
            'match_type' => $redirect->matchType()->value,
            'status' => $redirect->enabled(),
            'edit_url' => $redirect->editUrl(),
            'editable' => User::current()->can('edit', $redirect),
            'deletable' => User::current()->can('delete', $redirect),
        ];
    }
}
