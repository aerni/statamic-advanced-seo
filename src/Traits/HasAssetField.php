<?php

namespace Aerni\AdvancedSeo\Traits;

trait HasAssetField
{
    protected function getAssetFieldConfig(array $config = []): array
    {
        return array_merge([
            'type' => 'assets',
            'container' => config('advanced-seo.social_images.container', 'assets'),
            'folder' => 'social_images',
            'max_files' => 1,
            'mode' => 'list',
            'allow_uploads' => true,
            'localizable' => true,
            'listable' => 'hidden',
        ], $config);
    }
}
