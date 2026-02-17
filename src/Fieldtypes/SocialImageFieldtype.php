<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\SocialImages\SocialImageGenerator;
use Aerni\AdvancedSeo\Support\Helpers;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Fieldtypes\Assets\Assets;

class SocialImageFieldtype extends Assets
{
    protected static $handle = 'social_image';

    protected $component = 'assets';

    protected $selectable = false;

    public function augment($value)
    {
        $parent = $this->field->parent();

        if (! ($parent instanceof Entry || $parent instanceof Term) || ! $parent->seo_generate_social_images) {
            return parent::augment($value);
        }

        $generator = $this->generator($parent);

        return $generator->asset() ?? $generator->generate();
    }

    protected function generator(Entry|Term $content): SocialImageGenerator
    {
        return SocialImage::openGraph()->for(Helpers::localizedContent($content));
    }
}
