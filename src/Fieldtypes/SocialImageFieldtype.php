<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\SocialImages\SocialImageGenerator;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\GraphQL;
use Statamic\Fields\Fieldtype;
use Statamic\GraphQL\Types\AssetInterface;

class SocialImageFieldtype extends Fieldtype
{
    protected $selectable = false;

    public function preload(): ?array
    {
        $parent = $this->field->parent();

        if (! $parent instanceof Entry) {
            return null;
        }

        $generator = $this->generator($parent);

        $generateOnSaveMessage = config('queue.default') === 'sync'
            ? trans('advanced-seo::messages.social_images_generator_save_sync')
            : trans('advanced-seo::messages.social_images_generator_save_queue');

        $message = config('advanced-seo.social_images.generator.generate_on_save', true)
            ? $generateOnSaveMessage
            : trans('advanced-seo::messages.social_images_generator_on_demand');

        return [
            'message' => $message,
            'image' => $generator->asset()?->absoluteUrl(),
        ];
    }

    public function augment($value): ?Asset
    {
        $parent = $this->field->parent();

        if (! $parent->seo_generate_social_images) {
            return null;
        }

        $generator = $this->generator($parent);

        return $generator->asset() ?? $generator->generate()->asset();
    }

    protected function generator(Entry $entry): SocialImageGenerator
    {
        return match ($this->config()['image_type']) {
            'open_graph' => SocialImage::openGraph()->for($entry),
            'twitter' => SocialImage::find("twitter_{$entry->seo_twitter_card}")->for($entry),
        };
    }

    public function toGqlType()
    {
        return GraphQL::type(AssetInterface::NAME);
    }
}
