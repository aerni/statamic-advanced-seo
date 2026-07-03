<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Statamic\Eloquent\Database\BaseModel;

class RedirectHitModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'redirect_hits';

    protected $primaryKey = 'redirect';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'count' => 'integer',
        ];
    }
}
