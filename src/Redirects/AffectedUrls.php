<?php

namespace Aerni\AdvancedSeo\Redirects;

use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Collection as StatamicCollection;
use Statamic\Contracts\Entries\Entry;

class AffectedUrls
{
    /** @var array<string, string> */
    public array $entries = [];

    /** @var list<array{site: string, collections: Collection<int, StatamicCollection>, path: ?string}> */
    public array $mounts = [];

    /** @var list<array{entry: Entry, path: ?string}> */
    public array $localizations = [];

    /** @var list<Entry> */
    public array $inheritedDates = [];

    public function __construct(public readonly string $key) {}
}
