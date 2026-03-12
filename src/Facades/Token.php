<?php

namespace Aerni\AdvancedSeo\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Aerni\AdvancedSeo\Tokens\Tokens for(mixed $parent)
 * @method static \Aerni\AdvancedSeo\Registries\TokenRegistry registry()
 * @method static ?string normalize(\Statamic\Fields\Value $value)
 * @method static ?string parse(?string $data, \Statamic\Fields\Field $field)
 *
 * @see \Aerni\AdvancedSeo\Tokens\TokenService
 */
class Token extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Aerni\AdvancedSeo\Tokens\TokenService::class;
    }
}
