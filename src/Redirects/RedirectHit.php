<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Contracts\RedirectHit as Contract;
use Aerni\AdvancedSeo\Facades\Redirect;
use Illuminate\Support\Carbon;
use Statamic\Contracts\Query\ContainsQueryableValues;
use Statamic\Data\ExistsAsFile;
use Statamic\Data\TracksQueriedColumns;
use Statamic\Data\TracksQueriedRelations;
use Statamic\Facades\Path;
use Statamic\Facades\Stache;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class RedirectHit implements ContainsQueryableValues, Contract
{
    use ExistsAsFile;
    use FluentlyGetsAndSets;
    use TracksQueriedColumns;
    use TracksQueriedRelations;

    protected $redirect;

    protected int $count = 0;

    protected ?int $lastHitAt = null;

    public function redirect(?string $redirect = null): string|self
    {
        return $this
            ->fluentlyGetOrSet('redirect')
            ->args(func_get_args());
    }

    public function count(?int $count = null): int|self
    {
        return $this
            ->fluentlyGetOrSet('count')
            ->args(func_get_args());
    }

    public function lastHitAt(?int $lastHitAt = null): int|self|null
    {
        return $this
            ->fluentlyGetOrSet('lastHitAt')
            ->args(func_get_args());
    }

    public function lastHitAtIso(): ?string
    {
        if (! $lastHitAt = $this->lastHitAt()) {
            return null;
        }

        return Carbon::createFromTimestamp($lastHitAt, 'UTC')->toIso8601String();
    }

    public function id(): string
    {
        return $this->redirect();
    }

    public function getQueryableValue(string $field)
    {
        return match ($field) {
            'redirect' => $this->redirect(),
            default => $this->{$field}(),
        };
    }

    public function path(): string
    {
        return Path::assemble(
            Stache::store('redirect-hits')->directory(),
            "{$this->id()}.yaml"
        );
    }

    public function fileData(): array
    {
        return [
            'count' => $this->count(),
            'last_hit_at' => $this->lastHitAt(),
        ];
    }

    public function save(): self
    {
        Redirect::hits()->save($this);

        return $this;
    }

    public function delete(): bool
    {
        Redirect::hits()->delete($this);

        return true;
    }
}
