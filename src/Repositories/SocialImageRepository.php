<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Content\SocialImage;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;

class SocialImageRepository
{
    public function types(): Collection
    {
        return collect([
            'og' => [
                'type' => 'og',
                'field' => 'seo_og_image',
                'width' => 1200,
                'height' => 628,
            ],
            'twitter' => [
                'type' => 'twitter',
                'field' => 'seo_twitter_image',
                'width' => 1200,
                'height' => 628,
            ],
        ]);
    }

    public function all(Entry $entry): array
    {
        return $this->types()->flatMap(function ($type) use ($entry) {
            return (new SocialImage($entry, $type))->generate();
        })->toArray();
    }

    public function openGraph(Entry $entry): array
    {
        return (new SocialImage($entry, $this->types()['og']))->generate();
    }

    public function twitter(Entry $entry): array
    {
        return (new SocialImage($entry, $this->types()['twitter']))->generate();
    }
}
