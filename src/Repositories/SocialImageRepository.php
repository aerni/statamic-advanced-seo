<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Facades\SeoGlobals;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalSet;

class SocialImageRepository
{
    protected array $types = [
        'og' => [
            'prefix' => 'og',
            'width' => 1200,
            'height' => 630,
        ],
        'twitter' => [
            'prefix' => 'twitter',
            'width' => 600,
            'height' => 335,
        ],
    ];

    public function find(string $type, string $id): ?string
    {
        $entry = Entry::find($id);
        $type = $this->types[$type];

        $fileName = "{$entry->slug()}-{$type['prefix']}.png";

        return Storage::disk('assets')->exists($fileName)
            ? Storage::disk('assets')->url($fileName)
            : null;
    }

    public function make(string $type, string $id): string
    {
        $entry = Entry::find($id);
        $type = $this->types[$type];

        $baseUrl = config('app.url');
        $templateUrl = "{$baseUrl}/seo/social-images/{$type['prefix']}/{$entry->id()}";
        $fileName = "{$entry->slug()}-{$type['prefix']}.png";

        Browsershot::url($templateUrl)
            ->windowSize($type['width'], $type['height'])
            ->save(Storage::disk('assets')->path($fileName));

        return Storage::disk('assets')->url($fileName);
    }

    public function shouldGenerate(string $id): bool
    {
        $entry = Entry::find($id);

        // Shouldn't generate if the generator was disabled in the config.
        if (! config('advanced-seo.social_images.generator.enabled', false)) {
            return false;
        }

        $globals = GlobalSet::find(SeoGlobals::handle())
            ->inSelectedSite()
            ->data()
            ->get('social_images_collections', []);

        // Shouldn't generate if the entry's collection is not selected.
        if (! in_array($entry->collection()->handle(), $globals)) {
            return false;
        }

        // Shouldn't generate if the entry's generator toggle is off.
        if (! $entry->get('generate_social_images')) {
            return false;
        }

        return true;
    }
}
