<?php

namespace Aerni\AdvancedSeo\Redirects;

use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Facades\Entry;

class RedirectDestinationEntries
{
    /** @var array<string, EntryContract|null> */
    protected array $entries = [];

    public function preload(iterable $ids): void
    {
        $ids = collect($ids)
            ->filter(fn ($id) => is_string($id) && $id !== '')
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $entries = Entry::query()
            ->whereIn('id', $ids->all())
            ->get()
            ->keyBy->id();

        $ids->each(fn (string $id) => $this->entries[$id] = $entries->get($id));
    }

    public function find(string $id): ?EntryContract
    {
        if (! array_key_exists($id, $this->entries)) {
            $this->entries[$id] = Entry::find($id);
        }

        return $this->entries[$id];
    }
}
