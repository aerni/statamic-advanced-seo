<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Statamic\Contracts\Entries\Entry;
use Statamic\Fields\Fieldtype;

class SocialImagesPreviewFieldtype extends Fieldtype
{
    protected $selectable = false;

    public function preload(): array
    {
        $parent = $this->field->parent();
        $type = $this->config()['image_type'];

        $meta = ['title' => $this->field->display()];

        if ($this->shouldDisplayImage($parent)) {
            $meta['image'] = $parent->augmentedValue(SocialImage::specs($type, $parent)['field'])?->value()?->absoluteUrl();
        }

        return $meta;
    }

    protected function shouldDisplayImage($parent): bool
    {
        if (! $parent instanceof Entry) {
            return false;
        }

        if (! $parent->augmentedValue('seo_generate_social_images')?->value()) {
            return false;
        }

        return true;
    }
}
