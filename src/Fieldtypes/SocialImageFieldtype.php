<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\GraphQL;
use Statamic\Fields\Fieldtype;
use Statamic\GraphQL\Types\AssetInterface;

class SocialImageFieldtype extends Fieldtype
{
    protected $selectable = false;

    public function preload(): array
    {
        $parent = $this->field->parent();

        $meta = [
            'message' => config('advanced-seo.social_images.generator.generate_on_save', true)
                ? trans('advanced-seo::messages.social_images_generator_on_save')
                : trans('advanced-seo::messages.social_images_generator_on_demand'),
        ];

        if (! $parent instanceof Entry) {
            return $meta;
        }

        $type = $this->config()['image_type'];
        $image = SocialImage::all($parent)->get($type);

        $meta['image'] = $image->asset()?->absoluteUrl();

        return $meta;
    }

    public function augment($value): ?Asset
    {
        $parent = $this->field->parent();

        if (! $parent->seo_generate_social_images) {
            return null;
        }

        $type = $this->config()['image_type'];
        $image = SocialImage::all($parent)->get($type);

        return $image->asset() ?? $image->generate()->asset();
    }

    public function toGqlType()
    {
        return GraphQL::type(AssetInterface::NAME);
    }
}
