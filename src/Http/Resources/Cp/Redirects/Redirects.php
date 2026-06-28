<?php

namespace Aerni\AdvancedSeo\Http\Resources\Cp\Redirects;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Statamic\CP\Column;
use Statamic\Http\Resources\CP\Concerns\HasRequestedColumns;

class Redirects extends ResourceCollection
{
    use HasRequestedColumns;

    public $collects = ListedRedirect::class;

    protected $blueprint;

    protected $columns;

    protected $columnPreferenceKey;

    public function blueprint($blueprint): static
    {
        $this->blueprint = $blueprint;

        return $this;
    }

    public function columnPreferenceKey($key): static
    {
        $this->columnPreferenceKey = $key;

        return $this;
    }

    protected function setColumns(): void
    {
        $columns = $this->blueprint->columns();

        $status = Column::make('status')
            ->listable(true)
            ->visible(true)
            ->defaultVisibility(true)
            ->defaultOrder($columns->count() + 1)
            ->sortable(false);

        $columns->put('status', $status);

        if ($key = $this->columnPreferenceKey) {
            $columns->setPreferred($key);
        }

        $this->columns = $columns->rejectUnlisted()->values();
    }

    public function toArray($request)
    {
        $this->setColumns();

        return $this->collection->each(function ($redirect) {
            $redirect->columns($this->requestedColumns());
        });
    }

    public function with($request)
    {
        return [
            'meta' => [
                'columns' => $this->visibleColumns(),
            ],
        ];
    }
}
