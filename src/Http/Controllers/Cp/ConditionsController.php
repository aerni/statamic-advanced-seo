<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Actions\GetDefaultsData;
use Aerni\AdvancedSeo\Conditions\ShowSitemapSettings;
use Aerni\AdvancedSeo\Conditions\ShowSocialImagesGenerator;
use Illuminate\Routing\Controller;
use Statamic\Facades\Data;

class ConditionsController extends Controller
{
    public function __invoke(string $id)
    {
        $data = GetDefaultsData::handle(Data::find($id));

        if (! $data) {
            return [];
        }

        return [
            'showSitemapSettings' => ShowSitemapSettings::handle($data),
            'showSocialImagesGenerator' => ShowSocialImagesGenerator::handle($data),
        ];
    }
}
