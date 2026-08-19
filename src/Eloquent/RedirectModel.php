<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Enums\RedirectOrigin;
use Aerni\AdvancedSeo\Enums\RedirectResponseCode;
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
}
