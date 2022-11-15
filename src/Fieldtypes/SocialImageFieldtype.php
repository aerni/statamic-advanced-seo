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

        $meta = [
            'message' => config('advanced-seo.social_images.generator.generate_on_save', true)
                ? trans('advanced-seo::messages.social_images_generator_on_save')
                : trans('advanced-seo::messages.social_images_generator_on_demand'),
        ];

        if (! $parent instanceof Entry) {
            return $meta;
        }

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

        if ($image->exists()) {
            return $image->absoluteUrl();
        }

        if ($parent->seo_generate_social_images) {
            return $image->generate()->absoluteUrl();
        }

        return null;
    }
}
