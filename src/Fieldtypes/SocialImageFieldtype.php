<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\SocialImages\SocialImageGenerator;
use Aerni\AdvancedSeo\Support\Helpers;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Fieldtypes\Assets\Assets;

use function Illuminate\Support\defer;

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

        // Resolve the existing asset or generate a new one on demand.
        // This ensures the first request always returns an image (e.g. social platform crawlers).
        $generator = $this->generator($parent);
        $asset = $generator->asset() ?? $generator->generate();

        // Persist the asset path to the entry file so the image survives
        // if the generator is later disabled without requiring a manual save.
        if ($value !== $asset->path()) {
            defer(function () use ($parent, $asset) {
                $parent->set('seo_og_image', $asset->path());
                $parent->saveQuietly();
            });
        }

        return $asset;
    }

    protected function generator(Entry|Term $content): SocialImageGenerator
    {
        return SocialImage::openGraph()->for(Helpers::localizedContent($content));
    }
}
