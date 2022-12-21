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
    protected static $generating = false;

    public function preload(): ?array
    {
        $parent = $this->field->parent();

        if (! $parent instanceof Entry) {
            return null;
        }

        $type = $this->config()['image_type'];
        $image = SocialImage::all($parent)->get($type);

        return [
            'message' => config('queue.default') === 'sync'
                ? trans('advanced-seo::messages.social_images_generator_save_sync')
                : trans('advanced-seo::messages.social_images_generator_save_queue'),
            'image' => $image->asset()?->absoluteUrl(),
        ];
    }

    public function augment($value): ?Asset
    {
        $parent = $this->field->parent();

        if (! $parent->seo_generate_social_images) {
            return null;
        }

        $type = $this->config()['image_type'];
        $image = SocialImage::all($parent)->get($type);

        return $image->asset();
    }

    public function toGqlType()
    {
        return GraphQL::type(AssetInterface::NAME);
    }
}
