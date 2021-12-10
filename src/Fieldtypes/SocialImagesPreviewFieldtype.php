<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Statamic\Fields\Fieldtype;

class SocialImagesPreviewFieldtype extends Fieldtype
{
    protected $selectable = false;

    public function preload(): array
    {
        $type = $this->config()['image_type'];
        $entry = $this->field->parent();

        $specs = SocialImage::specs($type, $entry);

        return [
            'image' => $entry->augmentedValue($specs['field'])?->value()?->absoluteUrl(),
            'width' => $specs['width'],
            'height' => $specs['height'],
            'title' => $this->field->display(),
        ];
    }
}
