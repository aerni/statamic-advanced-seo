<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Features\SocialImagesGenerator;
use Aerni\AdvancedSeo\SocialImages\SocialImageGenerator;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Support\Facades\Log;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Fieldtypes\Assets\Assets;

use function Illuminate\Support\defer;

class SocialImageFieldtype extends Assets
{
    protected $component = 'assets';

    protected $selectable = false;

    public function augment($value)
    {
        $augmented = $this->shouldGenerateSocialImage()
            ? $this->augmentGeneratedSocialImage($value)
            : parent::augment($value);

        return $this->withExistingFile($augmented);
    }

    /**
     * Resolve an asset path without generating a replacement.
     */
    public function augmentWithoutGenerating(mixed $value): mixed
    {
        return $this->withExistingFile(parent::augment($value));
    }

    public function preProcessIndex($data)
    {
        if (! $this->shouldGenerateSocialImage()) {
            return parent::preProcessIndex($data);
        }

        // Listings show an already-generated image but never generate one on render.
        $asset = $this->generator($this->field->parent())->asset();

        return $asset
            ? parent::preProcessIndex($asset->path())
            : ['total' => 0, 'assets' => collect()];
    }

    protected function augmentGeneratedSocialImage($value)
    {
        $parent = $this->field->parent();

        // Resolve the existing asset or generate a new one on demand.
        // This ensures the first request always returns an image (e.g. social platform crawlers).
        try {
            $generator = $this->generator($parent);
            $asset = $generator->asset() ?? $generator->generate();
        } catch (\Throwable $e) {
            Log::warning('Unable to generate a social image.', ['exception' => $e]);

            return null;
        }

        if (! $asset instanceof Asset) {
            return null;
        }

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

    /**
     * A record whose file is gone must not be augmented. Reading its meta
     * asks Flysystem for the file size and takes down the response.
     */
    protected function withExistingFile(mixed $asset): mixed
    {
        if ($asset instanceof Asset && ! $asset->container()->disk()->exists($asset->path())) {
            return null;
        }

        return $asset;
    }

    protected function shouldGenerateSocialImage(): bool
    {
        $parent = $this->field->parent();

        if (! ($parent instanceof Entry || $parent instanceof Term)) {
            return false;
        }

        if (! SocialImagesGenerator::enabled(Context::from($parent))) {
            return false;
        }

        return $parent->seo_generate_social_images;
    }

    protected function generator(Entry|Term $content): SocialImageGenerator
    {
        return SocialImage::openGraph()->for(Helpers::localizedContent($content));
    }
}
