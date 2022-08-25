<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Actions\GetDefaultsDataFromUrl;
use Aerni\AdvancedSeo\Conditions\ShowSitemapFields;
use Aerni\AdvancedSeo\Conditions\ShowSocialImagesGeneratorFields;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ConditionsController extends Controller
{
    public function __invoke(Request $request): array
    {
        $data = GetDefaultsDataFromUrl::handle($request->get('href'));

        if (! $data) {
            return [];
        }

        return [
            'showSitemapFields' => ShowSitemapFields::handle($data),
            'showSocialImagesGeneratorFields' => ShowSocialImagesGeneratorFields::handle($data),
        ];
    }
}
