<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Support\Helpers;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Fields\Fieldtype;

class SocialPreviewFieldtype extends Fieldtype
{
    protected $selectable = false;

    public function preload(): array
    {
        $parent = $this->field()->parent();
        $defaults = Seo::find('site::defaults')->in(Context::from($parent)->site);

        return [
            'domain' => parse_url($defaults->site()->absoluteUrl(), PHP_URL_HOST),
            'imagePresets' => config('advanced-seo.social_images.presets'),
            'twitterCard' => $defaults->value('twitter_card'),
            'imageTemplateUrl' => $this->imageTemplateUrl($parent),
        ];
    }

    protected function imageTemplateUrl(mixed $parent): ?string
    {
        if (! ($parent instanceof Entry || $parent instanceof Term)) {
            return null;
        }

        $content = Helpers::localizedContent($parent);

        if (! $content->id()) {
            return null;
        }

        return SocialImage::openGraph()->url('{theme}', $content->id(), $content->locale());
    }
}
