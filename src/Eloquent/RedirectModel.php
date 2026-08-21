<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Actions\GenerateRedirectSourceHash;
use Aerni\AdvancedSeo\Enums\RedirectOrigin;
use Aerni\AdvancedSeo\Enums\RedirectResponseCode;
use Aerni\AdvancedSeo\Enums\RedirectSourceType;
use Statamic\Eloquent\Database\BaseModel;

class RedirectModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'redirects';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'preserve_query_string' => 'boolean',
            'origin' => RedirectOrigin::class,
            'response_code' => RedirectResponseCode::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $model->source_hash = GenerateRedirectSourceHash::handle((string) $model->source);
            $model->source_type = RedirectSourceType::fromSource((string) $model->source)->value;
        });
    }
}
