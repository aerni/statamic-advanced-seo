<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Statamic\Fields\Fieldtype;
use Statamic\Contracts\Entries\Entry;
use Statamic\Taxonomies\LocalizedTerm;
use Aerni\AdvancedSeo\Facades\SocialImage;

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

        if (! $parent->value('seo_generate_social_images')) {
            return false;
        }

        return true;
    }
}
