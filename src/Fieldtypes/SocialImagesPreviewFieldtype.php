<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Statamic\Fields\Fieldtype;

class SocialImagesPreviewFieldtype extends Fieldtype
{
    protected $selectable = false;

    public function preload(): array
    {
        $specs = SocialImage::types()[$this->config()['image_type']];

        return [
            'image' => $this->field->parent()->augmentedValue($specs['field'])?->value()?->absoluteUrl(),
            'width' => $specs['width'],
            'height' => $specs['height'],
            'title' => $this->field->display(),
        ];
    }
}
