<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Contracts\Redirect as Contract;
use Aerni\AdvancedSeo\Contracts\RedirectHit;
use Aerni\AdvancedSeo\Enums\Origin;
use Aerni\AdvancedSeo\Enums\ResponseCode;
use Aerni\AdvancedSeo\Enums\SourceType;
use Aerni\AdvancedSeo\Events\RedirectCreated;
use Aerni\AdvancedSeo\Events\RedirectDeleted;
use Aerni\AdvancedSeo\Events\RedirectSaved;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Illuminate\Support\Carbon;
use Statamic\Contracts\Query\ContainsQueryableValues;
use Statamic\Data\ExistsAsFile;
use Statamic\Data\TracksQueriedColumns;
use Statamic\Data\TracksQueriedRelations;
use Statamic\Facades\Entry;
use Statamic\Facades\Path;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\Facades\URL;
use Statamic\Support\Str;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class Redirect implements ContainsQueryableValues, Contract
{
    use ExistsAsFile;
    use FluentlyGetsAndSets;
    use TracksQueriedColumns;
    use TracksQueriedRelations;

    protected $id;

    protected $source;

    protected $destination;

    protected ResponseCode $responseCode = ResponseCode::Permanent;

    protected $site;

    protected $enabled = true;

    protected $preserveQueryString = true;

    protected Origin $origin = Origin::Manual;

    protected $description;

    protected ?int $createdAt = null;

    public function id(?string $id = null): string|self
    {
        return $this
            ->fluentlyGetOrSet('id')
            ->getter(fn ($id) => $id ?? ($this->id = Stache::generateId()))
            ->args(func_get_args());
    }

    public function source(?string $source = null): string|self|null
    {
        return $this
            ->fluentlyGetOrSet('source')
            ->setter(function (?string $source): ?string {
                if ($source === null) {
                    return $source;
                }

                if (SourceType::fromSource($source) === SourceType::Regex) {
                    return $source;
                }

                return Str::lower(RedirectPatternMatcher::normalizePath($source));
            })
            ->args(func_get_args());
    }

    public function destination(?string $destination = null): string|self|null
    {
        return $this
            ->fluentlyGetOrSet('destination')
            ->args(func_get_args());
    }

    public function sourceUrl(): ?string
    {
        $sourceType = $this->sourceType();

        if ($sourceType === SourceType::Regex) {
            return null;
        }

        $source = $this->source();

        if ($sourceType === SourceType::Wildcard) {
            $index = 0;
            $source = preg_replace_callback('/\*/', function () use (&$index) {
                $index++;

                return "wildcard{$index}";
            }, $source);
        }

        return URL::assemble(Site::get($this->site())->absoluteUrl(), $source);
    }

    public function destinationUrl(): ?string
    {
        $destination = $this->destination();

        if ($destination === null) {
            return null;
        }

        if (Str::startsWith($destination, 'entry::')) {
            return Entry::find(Str::after($destination, 'entry::'))?->absoluteUrl();
        }

        if (Str::startsWith($destination, ['http://', 'https://'])) {
            return $destination;
        }

        return URL::assemble(Site::get($this->site())->url(), Str::ensureLeft($destination, '/'));
    }

    /**
     * Whether the redirect produces a response: a Gone redirect always does,
     * otherwise it needs a resolvable destination.
     */
    public function resolves(): bool
    {
        return $this->responseCode() === ResponseCode::Gone || filled($this->destinationUrl());
    }

    public function responseCode(?ResponseCode $responseCode = null): ResponseCode|self
    {
        return $this
            ->fluentlyGetOrSet('responseCode')
            ->args(func_get_args());
    }

    public function sourceType(): SourceType
    {
        return SourceType::fromSource($this->source() ?? '');
    }

    public function site(?string $site = null): string|self
    {
        return $this
            ->fluentlyGetOrSet('site')
            ->getter(fn ($site) => $site ?? Site::default()->handle())
            ->args(func_get_args());
    }

    public function enabled(?bool $enabled = null): bool|self
    {
        return $this
            ->fluentlyGetOrSet('enabled')
            ->args(func_get_args());
    }

    public function preserveQueryString(?bool $preserveQueryString = null): bool|self
    {
        return $this
            ->fluentlyGetOrSet('preserveQueryString')
            ->args(func_get_args());
    }

    public function origin(?Origin $origin = null): Origin|self
    {
        return $this
            ->fluentlyGetOrSet('origin')
            ->args(func_get_args());
    }

    public function description(?string $description = null): string|self|null
    {
        return $this
            ->fluentlyGetOrSet('description')
            ->args(func_get_args());
    }

    public function createdAt(?int $createdAt = null): int|self|null
    {
        return $this
            ->fluentlyGetOrSet('createdAt')
            ->args(func_get_args());
    }

    public function createdAtIso(): ?string
    {
        if (! $createdAt = $this->createdAt()) {
            return null;
        }

        return Carbon::createFromTimestamp($createdAt, 'UTC')->toIso8601String();
    }

    public function getQueryableValue(string $field)
    {
        return match ($field) {
            'response_code' => $this->responseCode()->value,
            'origin' => $this->origin()->value,
            'created_at' => $this->createdAt(),
            default => $this->{$field}(),
        };
    }

    public function hit(): ?RedirectHit
    {
        return RedirectFacade::hits()->find($this->id());
    }

    public function editUrl(): string
    {
        return cp_route('advanced-seo.redirects.edit', $this->id());
    }

    public function path(): string
    {
        return Path::assemble(
            Stache::store('redirects')->directory(),
            $this->site(),
            "{$this->id()}.yaml"
        );
    }

    public function fileData(): array
    {
        return [
            'source' => $this->source(),
            'destination' => $this->responseCode() === ResponseCode::Gone ? null : $this->destination(),
            'response_code' => $this->responseCode()->value,
            'enabled' => $this->enabled(),
            'preserve_query_string' => $this->responseCode() === ResponseCode::Gone ? null : $this->preserveQueryString(),
            'origin' => $this->origin()->value,
            'description' => $this->description(),
            'created_at' => $this->createdAt(),
        ];
    }

    public function save(): self
    {
        $isNew = is_null(RedirectFacade::find($this->id()));

        if ($isNew && is_null($this->createdAt)) {
            $this->createdAt(Carbon::now()->timestamp);
        }

        RedirectFacade::save($this);

        if ($isNew) {
            RedirectCreated::dispatch($this);
        }

        RedirectSaved::dispatch($this);

        return $this;
    }

    public function saveQuietly(): self
    {
        $isNew = is_null(RedirectFacade::find($this->id()));

        if ($isNew && is_null($this->createdAt)) {
            $this->createdAt(Carbon::now()->timestamp);
        }

        RedirectFacade::save($this);

        return $this;
    }

    public function delete(): bool
    {
        RedirectFacade::delete($this);

        RedirectDeleted::dispatch($this);

        return true;
    }
}
