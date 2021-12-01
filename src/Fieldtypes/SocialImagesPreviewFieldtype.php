<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Statamic\Fields\Fieldtype;

class SocialImagesPreviewFieldtype extends Fieldtype
{
    protected $selectable = false;

    public function preload(): array
    {
        $type = $this->config()['image_type'];

        return [
            'image' => $this->field->parent()->augmentedValue("seo_{$type}_image")->value()->absoluteUrl(),
        ];
    }
}
