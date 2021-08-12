<?php

namespace Aerni\AdvancedSeo\Repositories;

use Statamic\Facades\Entry;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Storage;

class SocialImageRepository
{
    protected array $types = [
        'og' => [
            'prefix'  => 'og',
            'width'   => 1200,
            'height'  => 630,
        ],
        'twitter' => [
            'prefix'  => 'twitter',
            'width'   => 600,
            'height'  => 335,
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

    public function shouldGenerate(string $type, string $id): bool
    {
        $entry = Entry::find($id);

        if (! config('advanced-seo.social_images.generator.enabled', false)) {
            return false;
        }

        if (! $entry->get("generate_{$type}_image")) {
            return false;
        }

        return true;
    }
}
