<?php

namespace Aerni\AdvancedSeo\Tokens\Normalizers;

use Aerni\AdvancedSeo\Tokens\TokenNormalizer;
use Statamic\Contracts\Query\Builder;
use Statamic\Fields\Value;
use Statamic\Support\Str;

class UsersTokenNormalizer extends TokenNormalizer
{
    public function fieldtype(): string
    {
        return 'users';
    }

    public function normalize(Value $value): ?string
    {
        $value = $value->value();

        if ($value instanceof Builder) {
            $names = $value->limit(5)->get()
                ->map->name()
                ->filter()
                ->values()
                ->all();

            return Str::makeSentenceList($names, 'and', false);
        }

        return $value?->name();
    }
}
