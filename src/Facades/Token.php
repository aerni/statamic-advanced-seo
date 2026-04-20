<?php

namespace Aerni\AdvancedSeo\Facades;

use Aerni\AdvancedSeo\Tokens\TokenService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Aerni\AdvancedSeo\Tokens\Tokens for(mixed $parent)
 * @method static ?string normalize(\Statamic\Fields\Value $value)
 * @method static ?string parse(?string $data, \Statamic\Fields\Field $field)
 * @method static \Illuminate\Support\Collection normalizers()
 * @method static \Illuminate\Support\Collection tokens()
 *
 * @see TokenService
 */
class Token extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TokenService::class;
    }
}
