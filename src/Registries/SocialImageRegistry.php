<?php

namespace Aerni\AdvancedSeo\Registries;

use Aerni\AdvancedSeo\Facades\SocialImageTheme;
use Aerni\AdvancedSeo\SocialImages\SocialImage;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;

class SocialImageRegistry extends Registry
{
    public function find(string $type): ?SocialImage
    {
        return $this->all()->firstWhere('type', $type);
    }

    public function openGraph(): SocialImage
    {
        return $this->find('open_graph');
    }

    /**
     * Get all generators for content.
     *
     * @return Collection<int, \Aerni\AdvancedSeo\SocialImages\SocialImageGenerator>
     */
    public function for(Entry|Term $content): Collection
    {
        $content = Helpers::localizedContent($content);

        return collect([
            $this->openGraph()->for($content),
        ]);
    }

    /**
     * Get preview targets for content.
     */
    public function previewTargets(Entry|Term $content): array
    {
        $content = Helpers::localizedContent($content);
        $theme = SocialImageTheme::resolveFor($content)->handle;

        return [
            [
                'label' => 'Social Image',
                'format' => $this->openGraph()->url($theme, '{id}', $content->locale()),
            ],
        ];
    }

    protected function items(): array
    {
        return [
            new SocialImage(type: 'open_graph', handle: 'og_image'),
        ];
    }
}
