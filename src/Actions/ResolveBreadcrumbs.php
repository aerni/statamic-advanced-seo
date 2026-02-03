<?php

namespace Aerni\AdvancedSeo\Actions;

use Illuminate\Support\Collection;
use Statamic\Facades\Data;
use Statamic\Facades\URL;

class ResolveBreadcrumbs
{
    public static function handle(array $segments, string $site): Collection
    {
        $segments = collect($segments);

        return $segments
            ->map(fn (string $segment, int $index) => URL::tidy($segments->slice(0, $index + 1)->join('/')))
            ->map(fn (string $uri) => Data::findByUri($uri, $site))
            ->filter()
            ->values()
            ->map(fn (mixed $item, $index) => [
                'position' => $index + 1,
                'title' => method_exists($item, 'title') ? $item->title() : $item->value('title'),
                'url' => $item->absoluteUrl(),
            ]);
    }
}
