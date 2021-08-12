<?php

namespace Aerni\AdvancedSeo\Fieldsets;

use Aerni\AdvancedSeo\Facades\Fieldset;
use Illuminate\Support\Collection;

class SeoEntryFieldset extends BaseFieldset
{
    protected string $display = 'Seo';

    protected function sections(): array
    {
        return [
            $this->seoTags(),
            $this->socialImagesGenerator(),
            $this->socialImagesGeneratorFields(),
            $this->openGraph(),
            $this->twitter(),
            $this->canonicalUrl(),
            $this->indexing(),
            $this->sitemap(),
            $this->jsonLd(),
        ];
    }

    protected function seoTags(): Collection
    {
        return Fieldset::find('entry/seo_tags');
    }

    protected function socialImagesGenerator(): ?Collection
    {
        return config('advanced-seo.social_images.generator.enabled', true)
            ? Fieldset::find('entry/social_images_generator')
            : null;
    }

    protected function socialImagesGeneratorFields(): ?Collection
    {
        return config('advanced-seo.social_images.generator.enabled', true)
            ? Fieldset::find('social_images_generator_fields')
            : null;
    }

    protected function openGraph(): Collection
    {
        return Fieldset::find('entry/open_graph');
    }

    protected function twitter(): Collection
    {
        return Fieldset::find('entry/twitter');
    }

    protected function canonicalUrl(): Collection
    {
        return Fieldset::find('entry/canonical_url');
    }

    protected function indexing(): Collection
    {
        return Fieldset::find('entry/indexing');
    }

    protected function sitemap(): Collection
    {
        return Fieldset::find('entry/sitemap');
    }

    protected function jsonLd(): Collection
    {
        return Fieldset::find('entry/json_ld');
    }
}
