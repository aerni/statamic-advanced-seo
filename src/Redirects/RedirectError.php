<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Contracts\RedirectError as Contract;
use Aerni\AdvancedSeo\Facades\Redirect;
use Illuminate\Support\Carbon;
use Statamic\Contracts\Query\ContainsQueryableValues;
use Statamic\Data\ExistsAsFile;
use Statamic\Data\TracksQueriedColumns;
use Statamic\Data\TracksQueriedRelations;
use Statamic\Facades\Path;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class RedirectError implements ContainsQueryableValues, Contract
{
    use ExistsAsFile;
    use FluentlyGetsAndSets;
    use TracksQueriedColumns;
    use TracksQueriedRelations;

    protected $id;

    protected $url;

    protected $site;

    protected int $count = 0;

    protected ?int $firstSeenAt = null;

    protected ?int $lastSeenAt = null;

    public function id(?string $id = null): string|self
    {
        return $this
            ->fluentlyGetOrSet('id')
            ->getter(fn ($id) => $id ?? ($this->id = Stache::generateId()))
            ->args(func_get_args());
    }

    public function url(?string $url = null): string|self|null
    {
        return $this
            ->fluentlyGetOrSet('url')
            ->args(func_get_args());
    }

    public function site(?string $site = null): string|self
    {
        return $this
            ->fluentlyGetOrSet('site')
            ->getter(fn ($site) => $site ?? Site::default()->handle())
            ->args(func_get_args());
    }

    public function count(?int $count = null): int|self
    {
        return $this
            ->fluentlyGetOrSet('count')
            ->args(func_get_args());
    }

    public function firstSeenAt(?int $firstSeenAt = null): int|self|null
    {
        return $this
            ->fluentlyGetOrSet('firstSeenAt')
            ->args(func_get_args());
    }

    public function lastSeenAt(?int $lastSeenAt = null): int|self|null
    {
        return $this
            ->fluentlyGetOrSet('lastSeenAt')
            ->args(func_get_args());
    }

    public function firstSeenAtIso(): ?string
    {
        if (! $firstSeenAt = $this->firstSeenAt()) {
            return null;
        }

        return Carbon::createFromTimestamp($firstSeenAt, 'UTC')->toIso8601String();
    }

    public function lastSeenAtIso(): ?string
    {
        if (! $lastSeenAt = $this->lastSeenAt()) {
            return null;
        }

        return Carbon::createFromTimestamp($lastSeenAt, 'UTC')->toIso8601String();
    }

    public function getQueryableValue(string $field)
    {
        return match ($field) {
            'first_seen_at' => $this->firstSeenAt(),
            'last_seen_at' => $this->lastSeenAt(),
            default => $this->{$field}(),
        };
    }

    public function path(): string
    {
        return Path::assemble(
            Stache::store('redirect-errors')->directory(),
            "{$this->id()}.yaml"
        );
    }

    public function fileData(): array
    {
        return [
            'url' => $this->url(),
            'site' => $this->site(),
            'count' => $this->count(),
            'first_seen_at' => $this->firstSeenAt(),
            'last_seen_at' => $this->lastSeenAt(),
        ];
    }

    public function save(): self
    {
        Redirect::errors()->save($this);

        return $this;
    }

    public function delete(): bool
    {
        Redirect::errors()->delete($this);

        return true;
    }
}
