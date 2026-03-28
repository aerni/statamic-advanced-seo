<?php

namespace Aerni\AdvancedSeo\SeoSets;

use Illuminate\Contracts\Support\Arrayable;
use Spatie\SchemaOrg\Type;
use Statamic\Contracts\Assets\Asset;

class SeoData implements Arrayable
{
    protected array $data = [];

    /**
     * Set the page title. Recommended max: 60 characters.
     */
    public function title(string $value): self
    {
        $this->data['seo_title'] = $value;

        return $this;
    }

    /**
     * Set the meta description. Recommended max: 160 characters.
     */
    public function description(string $value): self
    {
        $this->data['seo_description'] = $value;

        return $this;
    }

    /**
     * Set the Open Graph title. Recommended max: 70 characters.
     */
    public function ogTitle(string $value): self
    {
        $this->data['seo_og_title'] = $value;

        return $this;
    }

    /**
     * Set the Open Graph description. Recommended max: 200 characters.
     */
    public function ogDescription(string $value): self
    {
        $this->data['seo_og_description'] = $value;

        return $this;
    }

    /**
     * Set the Open Graph image. Accepts a Statamic Asset, URL, or path.
     */
    public function ogImage(string|Asset $value): self
    {
        $this->data['seo_og_image'] = $value instanceof Asset ? $value->path() : $value;

        return $this;
    }

    /**
     * Mark the page as noindex.
     */
    public function noindex(): self
    {
        $this->data['seo_noindex'] = true;

        return $this;
    }

    /**
     * Mark the page as nofollow.
     */
    public function nofollow(): self
    {
        $this->data['seo_nofollow'] = true;

        return $this;
    }

    /**
     * Set a custom canonical URL.
     */
    public function canonicalUrl(string $value): self
    {
        $this->data['seo_canonical_type'] = 'custom';
        $this->data['seo_canonical_custom'] = $value;

        return $this;
    }

    /**
     * Set page-level JSON-LD structured data.
     * Accepts a raw JSON string or a Spatie\SchemaOrg\Type instance.
     */
    public function jsonLd(string|Type $value): self
    {
        $this->data['seo_json_ld'] = $value instanceof Type
            ? json_encode($value->toArray(), JSON_UNESCAPED_UNICODE)
            : $value;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['seo_enabled' => true, ...$this->data];
    }
}
