<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Data\DefaultsData;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\Term;

class GetDefaultsDataFromUrl
{
    public static function handle(string $url): ?DefaultsData
    {
        $url = parse_url($url);
        $path = array_slice(explode('/', $url['path']), 2);
        parse_str($url['query'] ?? '', $query);

        /**
         * We can only handle conditions on defaults, entry, or term routes.
         * If we have less than 3 path elements, we are not on one of those.
         */
        if (count($path) < 3) {
            return null;
        }

        return match ($path[0]) {
            ('advanced-seo') => self::handleDefaultsRoute($path, $query),
            ('collections') => self::handleEntryRoute($path),
            ('taxonomies') => self::handleTermRoute($path),
            default => null,
        };
    }

    protected static function handleDefaultsRoute(array $path, array $query): DefaultsData
    {
        return new DefaultsData(
            type: $path[1],
            handle: $path[2],
            // TODO: Might be better to get the default site of the collection/taxonomy instead of falling back to the selected site.
            locale: $query['site'] ?? Site::selected()->handle(),
        );
    }

    protected static function handleEntryRoute(array $path): DefaultsData
    {
        return GetDefaultsData::handle(Entry::find($path[3]));
    }

    protected static function handleTermRoute(array $path): DefaultsData
    {
        $data = GetDefaultsData::handle(Term::find("{$path[1]}::{$path[3]}"));

        /**
         * EvaluateModelLocale always returns the default locale because this request is not from a Statamic::isCpRoute().
         * Instead of making things complicated, we simply override the locale here.
         */
        $data->locale = $path[4];

        return $data;
    }
}
