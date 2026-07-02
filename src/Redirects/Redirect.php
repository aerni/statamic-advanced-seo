<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Contracts\Redirect as Contract;
use Aerni\AdvancedSeo\Enums\MatchType;
use Aerni\AdvancedSeo\Enums\ResponseCode;
use Aerni\AdvancedSeo\Events\RedirectCreated;
use Aerni\AdvancedSeo\Events\RedirectDeleted;
use Aerni\AdvancedSeo\Events\RedirectSaved;
use Aerni\AdvancedSeo\Facades\Redirects;
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

    protected $forwardQueryString = true;

    protected $automatic = false;

    protected $description;

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

                if (MatchType::fromSource($source) === MatchType::Regex) {
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
        $matchType = $this->matchType();

        if ($matchType === MatchType::Regex) {
            return null;
        }

        $source = $this->source();

        if ($matchType === MatchType::Wildcard) {
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

    public function responseCode(?ResponseCode $responseCode = null): ResponseCode|self
    {
        return $this
            ->fluentlyGetOrSet('responseCode')
            ->args(func_get_args());
    }

    public function matchType(): MatchType
    {
        return MatchType::fromSource($this->source() ?? '');
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

    public function forwardQueryString(?bool $forwardQueryString = null): bool|self
    {
        return $this
            ->fluentlyGetOrSet('forwardQueryString')
            ->args(func_get_args());
    }

    public function automatic(?bool $automatic = null): bool|self
    {
        return $this
            ->fluentlyGetOrSet('automatic')
            ->args(func_get_args());
    }

    public function description(?string $description = null): string|self|null
    {
        return $this
            ->fluentlyGetOrSet('description')
            ->args(func_get_args());
    }

    public function getQueryableValue(string $field)
    {
        return match ($field) {
            'response_code' => $this->responseCode()->value,
            default => $this->{$field}(),
        };
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
            'destination' => $this->destination(),
            'response_code' => $this->responseCode()->value,
            'enabled' => $this->enabled(),
            'forward_query_string' => $this->responseCode() === ResponseCode::Gone ? null : $this->forwardQueryString(),
            'automatic' => $this->automatic(),
            'description' => $this->description(),
        ];
    }

    public function save(): self
    {
        $isNew = is_null(Redirects::find($this->id()));

        Redirects::save($this);

        if ($isNew) {
            RedirectCreated::dispatch($this);
        }

        RedirectSaved::dispatch($this);

        return $this;
    }

    public function delete(): bool
    {
        Redirects::delete($this);

        RedirectDeleted::dispatch($this);

        return true;
    }
}
