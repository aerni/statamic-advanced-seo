<?php

namespace Aerni\AdvancedSeo\SocialImages;

use Aerni\AdvancedSeo\Models\SocialImage as SocialImageModel;
use Aerni\AdvancedSeo\Models\SocialImageTheme;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;

class SocialImageRepository
{
    public function all(Entry $entry): Collection
    {
        return SocialImageModel::groups()
            ->map(fn ($group, $type) => $this->{Str::camel($type)}($entry));
    }

    public function findModel(string $type): ?array
    {
        return SocialImageModel::firstWhere('type', $type);
    }

    public function openGraph(Entry $entry): SocialImage
    {
        return new SocialImage($entry, $this->findModel('open_graph'));
    }

    public function twitter(Entry $entry): SocialImage
    {
        return new SocialImage($entry, $this->findModel("twitter_{$entry->seo_twitter_card}"));
    }

    public function previewTargets(Entry $entry): array
    {
        return [
            [
                'label' => 'Open Graph Image',
                'format' => $this->route(theme: $entry->seo_social_images_theme ?? SocialImageTheme::fieldtypeDefault(), type: 'open_graph', id: '{id}'),
            ],
            [
                'label' => 'Twitter Image',
                'format' => $this->route(theme: $entry->seo_social_images_theme ?? SocialImageTheme::fieldtypeDefault(), type: "twitter_{$entry->seo_twitter_card}", id: '{id}'),
            ],
        ];
    }

    public function route(string $theme, string $type, string $id): string
    {
        return "/!/advanced-seo/social-images/{$theme}/{$type}/{$id}";
    }

    public function sizeString(string $type): string
    {
        $type = $this->findModel($type);

        return "{$type['width']} x {$type['height']} pixels";
    }
}
