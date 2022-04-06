<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Statamic\Contracts\Entries\Entry;
use Statamic\Fields\Fieldtype;

class SocialImageFieldtype extends Fieldtype
{
    protected $selectable = false;

    public function preload(): array
    {
        $parent = $this->field->parent();
        $type = $this->config()['image_type'];

        $meta = ['title' => $this->field->display()];

        if ($this->shouldDisplayImage($parent)) {
            $field = SocialImage::specs($type, $parent)['field'];
            $meta['image'] = $parent->{$field}?->absoluteUrl();
        }

        return $meta;
    }

    protected function shouldDisplayImage($parent): bool
    {
        if (! $parent instanceof Entry) {
            return false;
        }

        if (! $parent->seo_generate_social_images) {
            return false;
        }

        return true;
    }
}
