<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Statamic\Fields\Fieldtype;

class SocialImageFieldtype extends Fieldtype
{
    protected $selectable = false;

    public function preload(): array
    {
        $meta = ['title' => $this->field->display()];

        $parent = $this->field->parent();
        $type = $this->config()['image_type'];

        $image = SocialImage::all($parent)->get($type);

        if ($image->exists()) {
            $meta['image'] = $image->absoluteUrl();
        }

        return $meta;
    }

    public function augment($value): ?string
    {
        $parent = $this->field->parent();
        $type = $this->config()['image_type'];

        $image = SocialImage::all($parent)->get($type);

        return $image->exists()
            ? $image->absoluteUrl()
            : null;
    }
}
