<?php

namespace Aerni\AdvancedSeo\Http\Resources\Cp\Redirects;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Statamic\CP\Column;
use Statamic\CP\Columns;
use Statamic\Facades\Site;
use Statamic\Http\Resources\CP\Concerns\HasRequestedColumns;

class Redirects extends ResourceCollection
{
    use HasRequestedColumns;

    public $collects = ListedRedirect::class;

    protected $columns;

    protected function setColumns(): void
    {
        $columns = new Columns([
            Column::make('source')->label(__('advanced-seo::fields.redirect_source.display'))->sortable(true)->defaultVisibility(true)->visible(true)->listable(true),
            Column::make('destination')->label(__('advanced-seo::fields.redirect_destination.display'))->sortable(true)->defaultVisibility(true)->visible(true)->listable(true),
            Column::make('type')->label(__('advanced-seo::fields.redirect_type.display'))->sortable(true)->defaultVisibility(true)->visible(true)->listable(true),
            Column::make('forward_query_string')->label(__('advanced-seo::fields.redirect_forward_query_string.display'))->sortable(false)->defaultVisibility(false)->visible(false)->listable(true),
        ]);

        if (Site::multiEnabled()) {
            $columns->push(
                Column::make('site')->label(__('Site'))->sortable(true)->defaultVisibility(true)->visible(true)->listable(true)
            );
        }

        $columns->push(
            Column::make('status')->label(__('Status'))->sortable(false)->defaultVisibility(true)->visible(true)->listable(true)
        );

        $columns->push(
            Column::make('description')->label(__('advanced-seo::fields.redirect_description.display'))->sortable(false)->defaultVisibility(false)->visible(false)->listable(true)
        );

        $columns->setPreferred('advanced-seo.redirects.columns');

        $this->columns = $columns->rejectUnlisted()->values();
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
                'columns' => $this->visibleColumns(),
            ],
        ];
    }
}
