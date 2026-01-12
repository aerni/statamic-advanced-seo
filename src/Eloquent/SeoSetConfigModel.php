<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Statamic\Eloquent\Database\BaseModel;

class SeoSetConfigModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'seo_set_configs';

    protected $casts = [
        'data' => AsCollection::class,
    ];
}
