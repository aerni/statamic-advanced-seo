<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Statamic\Eloquent\Database\BaseModel;

class RedirectErrorModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'redirect_errors';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'count' => 'integer',
            'first_seen_at' => 'integer',
            'last_seen_at' => 'integer',
        ];
    }
}
