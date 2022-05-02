<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Content\SocialImage;
use Aerni\AdvancedSeo\Models\SocialImage as SocialImageModel;
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
        $card = $entry->seo_twitter_card ?? 'summary';

        return new SocialImage($entry, $this->findModel("twitter_{$card}"));
    }

    public function previewTargets(): array
    {
        return [
            [
                'label' => 'Open Graph Image',
                'format' => $this->route(type: 'open_graph', theme: '{seo_social_images_theme}', id: '{id}'),
            ],
            [
                'label' => 'Twitter Image',
                'format' => $this->route(type: "twitter_{seo_twitter_card}", theme: '{seo_social_images_theme}', id: '{id}'),
            ],
        ];
    }

    public function route(string $type, string $theme, string $id): string
    {
        return "/!/advanced-seo/social-images/{$type}/{$theme}/{$id}";
    }

    public function sizeString(string $type): string
    {
        $type = $this->findModel($type);

        return "{$type['width']} x {$type['height']} pixels";
    }
}
