<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Actions\GetDefaultsData;
use Aerni\AdvancedSeo\Actions\ShouldDisplaySocialImagesGenerator;
use Illuminate\Routing\Controller;
use Statamic\Facades\Data;

class ConditionsController extends Controller
{
    public function __invoke(string $id)
    {
        $data = GetDefaultsData::handle(Data::find($id));

        return [
            'showSocialImagesGenerator' => ShouldDisplaySocialImagesGenerator::handle($data),
        ];
    }
}
