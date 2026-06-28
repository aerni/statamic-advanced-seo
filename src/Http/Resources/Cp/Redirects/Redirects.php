<?php

namespace Aerni\AdvancedSeo\Http\Resources\Cp\Redirects;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Statamic\CP\Column;
use Statamic\Facades\Site;

class Redirects extends ResourceCollection
{
    public $collects = ListedRedirect::class;

    protected $columns;

    protected function setColumns(): void
    {
        $columns = collect([
            Column::make('source')->label(__('advanced-seo::fields.redirect_source.display'))->sortable(true)->defaultVisibility(true)->visible(true)->listable(true),
            Column::make('destination')->label(__('advanced-seo::fields.redirect_destination.display'))->sortable(true)->defaultVisibility(true)->visible(true)->listable(true),
            Column::make('type')->label(__('advanced-seo::fields.redirect_type.display'))->sortable(true)->defaultVisibility(true)->visible(true)->listable(true),
        ]);

        if (Site::multiEnabled()) {
            $columns->push(
                Column::make('site')->label(__('Site'))->sortable(true)->defaultVisibility(true)->visible(true)->listable(true)
            );
        }

        $columns->push(
            Column::make('status')->label(__('Status'))->sortable(false)->defaultVisibility(true)->visible(true)->listable(true)
        );

        $this->columns = $columns->values();
    }

    public function toArray($request)
    {
        $this->setColumns();

        return $this->collection;
    }

    public function with($request)
    {
        $this->setColumns();

        return [
            'meta' => [
                'columns' => $this->columns,
            ],
        ];
    }
}
