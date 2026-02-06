<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Fields\Fieldtype;

class SocialPreviewFieldtype extends Fieldtype
{
    protected static $handle = 'social_preview';

    protected $selectable = false;

    public function preload(): array
    {
        $parent = $this->field()->parent();
        $context = Context::from($parent);
        $general = Seo::find('site::general')->in($context->site);

        return [
            'domain' => parse_url($general->site()->absoluteUrl(), PHP_URL_HOST),
            'imagePresets' => config('advanced-seo.social_images.presets'),
            'twitterCard' => $context->seoSet()->config()->value('twitter_card'),
        ];
    }
}
