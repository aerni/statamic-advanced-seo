<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Enums\ResponseCode;
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
            'forward_query_string' => 'boolean',
            'automatic' => 'boolean',
            'response_code' => ResponseCode::class,
        ];
    }
}
