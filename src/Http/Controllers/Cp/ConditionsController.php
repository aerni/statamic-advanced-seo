<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Actions\GetDefaultsData;
use Aerni\AdvancedSeo\Conditions\ShowSitemapFields;
use Aerni\AdvancedSeo\Conditions\ShowSocialImagesGeneratorFields;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Statamic\Facades\Data;

class ConditionsController extends Controller
{
    public function __invoke(Request $request): array
    {
        $id = $request->get('id');

        $model = Data::find($id) ?? Seo::findById($id);

        $data = GetDefaultsData::handle($model);

        if (! $data) {
            return [];
        }

        /**
         * We have to manually set the locale if a site query exists.
         * The locale can't be correctly evaluated in EvaluateModelLocale because the request
         * is coming from this controller and doesn't have the site query.
         */
        if ($site = $request->get('site')) {
            $data->locale = $site;
        }

        return [
            'showSitemapFields' => ShowSitemapFields::handle($data),
            'showSocialImagesGeneratorFields' => ShowSocialImagesGeneratorFields::handle($data),
        ];
    }
}
