<?php

namespace Aerni\AdvancedSeo\Models;

use Illuminate\Support\Collection;
use Statamic\Facades\Blink;

abstract class Registry
{
    abstract protected static function make(): Collection|array;

    protected static function all(): Collection
    {
        return Blink::once(static::class.'::all', fn () => collect(static::make()));
    }

    public static function __callStatic($method, $parameters)
    {
        if (method_exists(static::class, $method)) {
            return static::$method(...$parameters);
        }

        return static::all()->{$method}(...$parameters);
    }
}
