<?php

use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesRedirects;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;
use Statamic\Facades\Site;
use Statamic\Facades\User;

uses(UseEloquentDriver::class, EnablesRedirects::class);

beforeEach(function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);
    $this->super = tap(User::make()->makeSuper())->save();
});

it('creates and moves a redirect via eloquent', function () {
    $this->actingAs($this->super)->post(cp_route('advanced-seo.redirects.store'), [
        'source' => '/old', 'destination' => '/new', 'response_code' => 301, 'enabled' => true, 'site' => 'default',
    ])->assertOk();

    $id = Redirect::query()->where('site', 'default')->where('source', '/old')->first()->id();

    $this->actingAs($this->super)->patch(cp_route('advanced-seo.redirects.update', $id), [
        'source' => '/old', 'destination' => '/new', 'response_code' => 301, 'enabled' => true, 'site' => 'french',
    ])->assertOk();

    expect(Redirect::query()->where('site', 'default')->where('source', '/old')->first())->toBeNull();
    expect(Redirect::query()->where('site', 'french')->where('source', '/old')->first())->not->toBeNull();
});
