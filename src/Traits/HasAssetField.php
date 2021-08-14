<?php

namespace Aerni\AdvancedSeo\Traits;

trait HasAssetField
{
    protected function getAssetFieldConfig(array $config = []): array
    {
        return array_merge([
            'type' => 'assets',
            'container' => config('advanced-seo.social-images.container', 'seo'),
            'folder' => 'social_images',
            'max_files' => 1,
            'mode' => 'list',
            'allow_uploads' => true,
            'localizable' => true,
            'listable' => 'hidden',
        ], $config);
    }
}
