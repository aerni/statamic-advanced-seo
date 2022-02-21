<?php

namespace Aerni\AdvancedSeo\Models;

use Illuminate\Support\Collection;

abstract class Model
{
    protected static Collection $rows;

    abstract protected static function getRows(): array;

    public static function __callStatic($method, $parameters)
    {
        static::$rows = collect(static::getRows());

        if (method_exists(static::class, $method)) {
            return static::$method(...$parameters);
        }

        return static::$rows->{$method}(...$parameters);
    }
}
