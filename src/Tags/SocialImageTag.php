<?php

namespace Aerni\AdvancedSeo\Tags;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Statamic\Tags\Tags;

class SocialImageTag extends Tags
{
    protected static $handle = 'social_image';

    public function og(): string
    {
        return $this->image('og');
    }

    public function twitter(): string
    {
        return $this->image('twitter');
    }

    public function image(string $type): string
    {
        $id = $this->context->get('id');

        if (SocialImage::shouldGenerate($type, $id)) {
            return SocialImage::find($type, $id) ?? SocialImage::make($type, $id);
        }

        return $this->context->get("{$type}_image");
    }
}
