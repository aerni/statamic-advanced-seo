<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Actions\EvaluateModelType;
use Aerni\AdvancedSeo\Actions\EvaluateModelSites;
use Aerni\AdvancedSeo\Actions\EvaluateModelHandle;
use Aerni\AdvancedSeo\Actions\EvaluateModelLocale;

class GetDefaultsData
{
    public static function handle(mixed $data): ?DefaultsData
    {
        if ($data instanceof DefaultsData) {
            return $data;
        }

        if (! $parent = EvaluateModelParent::handle($data)) {
            return null;
        }

        return new DefaultsData(
            type: EvaluateModelType::handle($parent),
            handle: EvaluateModelHandle::handle($parent),
            locale: EvaluateModelLocale::handle($data),
            sites: EvaluateModelSites::handle($parent),
        );
    }
}
