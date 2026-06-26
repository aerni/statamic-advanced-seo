<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Contracts\Redirect as Contract;
use Aerni\AdvancedSeo\Enums\MatchType;
use Aerni\AdvancedSeo\Enums\RedirectType;
use Aerni\AdvancedSeo\Events\RedirectCreated;
use Aerni\AdvancedSeo\Events\RedirectDeleted;
use Aerni\AdvancedSeo\Events\RedirectSaved;
use Aerni\AdvancedSeo\Facades\Redirects;
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

class Redirect implements Contract
{
    use ExistsAsFile;
    use FluentlyGetsAndSets;
    use TracksQueriedColumns;
    use TracksQueriedRelations;

    protected $id;

    protected $source;

    protected $destination;

    protected RedirectType $type = RedirectType::Permanent;

    protected $site;

    protected $enabled = true;

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

    public function destinationUrl(): ?string
    {
        $destination = $this->destination();

        if ($destination === null) {
            return null;
        }

        if (Str::startsWith($destination, 'entry::')) {
            return Entry::find(Str::after($destination, 'entry::'))?->in($this->site())?->absoluteUrl();
        }

        if (! Str::startsWith($destination, '/')) {
            return $destination;
        }

        return URL::assemble(Site::get($this->site())->url(), $destination);
    }

    public function type(?RedirectType $type = null): RedirectType|self
    {
        return $this
            ->fluentlyGetOrSet('type')
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

    public function description(?string $description = null): string|self|null
    {
        return $this
            ->fluentlyGetOrSet('description')
            ->args(func_get_args());
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
            'type' => $this->type()->value,
            'enabled' => $this->enabled(),
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
