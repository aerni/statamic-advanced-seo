<?php

use Aerni\AdvancedSeo\Facades\Redirects;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;
use Statamic\Facades\Site;
use Statamic\Facades\User;

uses(UseEloquentDriver::class);

beforeEach(function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);
    $this->super = tap(User::make()->makeSuper())->save();
});

it('creates and moves a redirect via eloquent', function () {
    $this->actingAs($this->super)->post(cp_route('advanced-seo.redirects.store'), [
        'source' => '/old', 'destination' => '/new', 'type' => 301, 'enabled' => true, 'site' => 'default',
    ])->assertOk();

    $id = Redirects::query()->where('site', 'default')->where('source', '/old')->first()->id();

    $this->actingAs($this->super)->patch(cp_route('advanced-seo.redirects.update', $id), [
        'source' => '/old', 'destination' => '/new', 'type' => 301, 'enabled' => true, 'site' => 'french',
    ])->assertOk();

    expect(Redirects::query()->where('site', 'default')->where('source', '/old')->first())->toBeNull();
    expect(Redirects::query()->where('site', 'french')->where('source', '/old')->first())->not->toBeNull();
});
