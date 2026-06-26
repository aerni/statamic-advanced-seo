<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Enums\RedirectType;
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
            'type' => RedirectType::class,
        ];
    }
}
