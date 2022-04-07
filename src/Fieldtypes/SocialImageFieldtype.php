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

        if (! $parent instanceof Entry) {
            return $meta;
        }

        $image = SocialImage::all($parent)->get($type);

        if ($image->exists()) {
            $meta['image'] = $image->absoluteUrl();
        }

        return $meta;
    }

    public function augment($value): string
    {
        $parent = $this->field->parent();
        $type = $this->config()['image_type'];

        $image = SocialImage::all($parent)->get($type);

        return $image->exists()
            ? $image->absoluteUrl()
            : $image->generate()->absoluteUrl();
    }
}
