<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Statamic\Eloquent\Database\BaseModel;

class SeoSetLocalizationModel extends BaseModel
{
    protected $guarded = [];

    protected $table = 'seo_set_localizations';

    protected function casts(): array
    {
        return [
            'data' => AsCollection::class,
        ];
    }
}
