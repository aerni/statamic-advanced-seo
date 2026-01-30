<?php

use Aerni\AdvancedSeo\Sitemaps\Custom\CustomSitemapUrl;
use Illuminate\Support\Carbon;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);
});

it('returns the provided loc', function () {
    $url = new CustomSitemapUrl('https://example.com/page');

    expect($url->loc())->toBe('https://example.com/page');
});

it('can set loc fluently', function () {
    $url = new CustomSitemapUrl('https://example.com/page');
    $url->loc('https://example.com/updated');

    expect($url->loc())->toBe('https://example.com/updated');
});

it('defaults lastmod to current time', function () {
    Carbon::setTestNow('2025-06-15 12:00:00');

    $url = new CustomSitemapUrl('https://example.com/page');

    expect($url->lastmod())->toStartWith('2025-06-15T12:00:00');

    Carbon::setTestNow();
});

it('can set lastmod with a carbon instance', function () {
    $url = new CustomSitemapUrl('https://example.com/page');
    $date = Carbon::parse('2025-03-15 10:30:00', 'UTC');

    $url->lastmod($date);

    expect($url->lastmod())->toStartWith('2025-03-15T10:30:00');
});

it('can set changefreq to all valid values', function () {
    $url = new CustomSitemapUrl('https://example.com/page');

    $validValues = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];

    foreach ($validValues as $value) {
        $url->changefreq($value);
        expect($url->changefreq())->toBe($value);
    }
});

it('throws an exception for an invalid changefreq', function () {
    $url = new CustomSitemapUrl('https://example.com/page');

    $url->changefreq('sometimes');
})->throws(Exception::class, 'Valid values are:');

it('can set priority to all valid values', function () {
    $url = new CustomSitemapUrl('https://example.com/page');

    $validValues = ['0.0', '0.1', '0.2', '0.3', '0.4', '0.5', '0.6', '0.7', '0.8', '0.9', '1.0'];

    foreach ($validValues as $value) {
        $url->priority($value);
        expect($url->priority())->toBe($value);
    }
});

it('throws an exception for an invalid priority', function () {
    $url = new CustomSitemapUrl('https://example.com/page');

    $url->priority('2.0');
})->throws(Exception::class, 'Valid values are:');

it('defaults site to the default site handle', function () {
    $url = new CustomSitemapUrl('https://example.com/page');

    expect($url->site())->toBe('english');
});

it('can set site fluently', function () {
    $url = new CustomSitemapUrl('https://example.com/page');
    $url->site('french');

    expect($url->site())->toBe('french');
});

it('defaults alternates to null', function () {
    $url = new CustomSitemapUrl('https://example.com/page');

    expect($url->alternates())->toBeNull();
});

it('can set alternates with valid data', function () {
    $url = new CustomSitemapUrl('https://example.com/page');

    $alternates = [
        ['href' => 'https://example.com/en/page', 'hreflang' => 'en'],
        ['href' => 'https://example.com/de/page', 'hreflang' => 'de'],
    ];

    $url->alternates($alternates);

    expect($url->alternates())->toBe($alternates);
});

it('throws an exception when alternates are missing href', function () {
    $url = new CustomSitemapUrl('https://example.com/page');

    $url->alternates([
        ['hreflang' => 'en'],
    ]);
})->throws(Exception::class, 'href');

it('throws an exception when alternates are missing hreflang', function () {
    $url = new CustomSitemapUrl('https://example.com/page');

    $url->alternates([
        ['href' => 'https://example.com/page'],
    ]);
})->throws(Exception::class, 'hreflang');

it('converts to array with expected structure', function () {
    Carbon::setTestNow('2025-06-15 12:00:00');

    $url = new CustomSitemapUrl('https://example.com/page');
    $url->changefreq('weekly');
    $url->priority('0.8');

    $array = $url->toArray();

    expect($array)
        ->toHaveKeys(['loc', 'alternates', 'lastmod', 'changefreq', 'priority', 'site'])
        ->and($array['loc'])->toBe('https://example.com/page')
        ->and($array['changefreq'])->toBe('weekly')
        ->and($array['priority'])->toBe('0.8')
        ->and($array['site'])->toBe('english')
        ->and($array['alternates'])->toBeNull();

    Carbon::setTestNow();
});

it('accepts all constructor parameters', function () {
    $url = new CustomSitemapUrl(
        loc: 'https://example.com/page',
        alternates: [
            ['href' => 'https://example.com/en/page', 'hreflang' => 'en'],
        ],
        lastmod: '2025-01-01T00:00:00+00:00',
        changefreq: 'daily',
        priority: '0.7',
        site: 'german',
    );

    expect($url->loc())->toBe('https://example.com/page')
        ->and($url->alternates())->toHaveCount(1)
        ->and($url->lastmod())->toBe('2025-01-01T00:00:00+00:00')
        ->and($url->changefreq())->toBe('daily')
        ->and($url->priority())->toBe('0.7')
        ->and($url->site())->toBe('german');
});
