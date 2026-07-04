<?php

namespace Aerni\AdvancedSeo\Http\Resources\Cp\Redirects;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Statamic\CP\Column;
use Statamic\CP\Columns;
use Statamic\Facades\Site;
use Statamic\Http\Resources\CP\Concerns\HasRequestedColumns;

class Errors extends ResourceCollection
{
    use HasRequestedColumns;

    public $collects = ListedError::class;

    public $handledChecker;

    protected $columns;

    protected function setColumns(): void
    {
        $columns = new Columns([
            Column::make('url')->label(__('advanced-seo::messages.redirect_error_url'))->sortable(false)->defaultVisibility(true)->visible(true)->listable(true),
            Column::make('hits')->label(__('advanced-seo::messages.redirect_hits'))->sortable(true)->defaultVisibility(true)->visible(true)->listable(true),
            Column::make('last_seen_at')->label(__('advanced-seo::messages.redirect_error_last_seen'))->sortable(true)->defaultVisibility(true)->visible(true)->listable(true),
            Column::make('handled')->label(__('advanced-seo::messages.redirect_error_handled'))->sortable(false)->defaultVisibility(true)->visible(true)->listable(true),
        ]);

        if (Site::multiEnabled()) {
            $columns->push(
                Column::make('site')->label(__('Site'))->sortable(true)->defaultVisibility(true)->visible(true)->listable(true)
            );
        }

        $columns->setPreferred('advanced-seo.redirect-errors.columns');

        $this->columns = $columns->rejectUnlisted()->values();
    }

    public function toArray($request)
    {
        $this->setColumns();

        return $this->collection->map(fn ($error) => (new ListedError($error))->handledChecker($this->handledChecker));
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
