<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Actions\GetDefaultsData;
use Aerni\AdvancedSeo\Conditions\ShowSitemapFields;
use Aerni\AdvancedSeo\Conditions\ShowSocialImagesGeneratorFields;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Routing\Controller;
use Statamic\Facades\Data;

class ConditionsController extends Controller
{
    public function __invoke(string $id): array
    {
        $model = Data::find($id) ?? Seo::findById($id);

        $data = GetDefaultsData::handle($model);

        if (! $data) {
            return [];
        }

        return [
            'showSitemapFields' => ShowSitemapFields::handle($data),
            'showSocialImagesGeneratorFields' => ShowSocialImagesGeneratorFields::handle($data),
        ];
    }
}
