<?php

namespace Aerni\AdvancedSeo\Http\Resources\Cp\Redirects;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Statamic\CP\Column;
use Statamic\CP\Columns;
use Statamic\Facades\Site;
use Statamic\Http\Resources\CP\Concerns\HasRequestedColumns;

class Redirects extends ResourceCollection
{
    use HasRequestedColumns;

    public $collects = ListedRedirect::class;

    protected $columns;

    protected ?Collection $hits = null;

    public function hits(?Collection $hits): static
    {
        $this->hits = $hits;

        return $this;
    }

    protected function setColumns(): void
    {
        $columns = new Columns([
            Column::make('source')->label(__('advanced-seo::fields.redirect_source.display'))->sortable(true)->defaultVisibility(true)->visible(true)->listable(true),
            Column::make('destination')->label(__('advanced-seo::fields.redirect_destination.display'))->sortable(true)->defaultVisibility(true)->visible(true)->listable(true),
            Column::make('response_code')->label(__('advanced-seo::fields.redirect_response_code.display'))->sortable(true)->defaultVisibility(true)->visible(true)->listable(true),
            Column::make('preserve_query_string')->label(__('advanced-seo::fields.redirect_preserve_query_string.display'))->sortable(true)->defaultVisibility(false)->visible(false)->listable(true),
        ]);

        if (Site::authorized()->count() > 1) {
            $columns->push(
                Column::make('site')->label(__('Site'))->sortable(true)->defaultVisibility(true)->visible(true)->listable(true)
            );
        }

        if (config('advanced-seo.redirects.hits.enabled')) {
            $columns->push(
                Column::make('hits')->label(__('advanced-seo::messages.redirect_hits'))->sortable(true)->defaultVisibility(true)->visible(true)->listable(true)
            );

            $columns->push(
                Column::make('last_hit_at')->label(__('advanced-seo::messages.redirect_last_hit_at'))->sortable(true)->defaultVisibility(true)->visible(true)->listable(true)
            );
        }

        $columns->push(
            Column::make('created_at')->label(__('advanced-seo::messages.redirect_created_at'))->sortable(true)->defaultVisibility(false)->visible(false)->listable(true)
        );

        $columns->push(
            Column::make('status')->label(__('Status'))->sortable(true)->defaultVisibility(true)->visible(true)->listable(true)
        );

        $columns->push(
            Column::make('origin')->label(__('advanced-seo::messages.redirect_origin'))->sortable(true)->defaultVisibility(false)->visible(false)->listable(true)
        );

        $columns->push(
            Column::make('description')->label(__('advanced-seo::fields.redirect_description.display'))->sortable(true)->defaultVisibility(false)->visible(false)->listable(true)
        );

        $columns->setPreferred('advanced-seo.redirects.columns');

        $this->columns = $columns->rejectUnlisted()->values();
    }

    public function toArray($request)
    {
        $this->setColumns();

        return $this->collection->each->hits($this->hits);
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
