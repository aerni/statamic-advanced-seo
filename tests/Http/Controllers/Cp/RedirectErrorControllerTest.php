<?php

use Aerni\AdvancedSeo\Facades\Redirect;
use Illuminate\Support\Carbon;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    config(['advanced-seo.redirects.errors.enabled' => true]);
    $this->user = User::make()->makeSuper()->save();
});

it('lists errors with a derived handled flag', function () {
    Redirect::errors()->make()->url('/handled')->site('default')->count(5)->lastSeenAt(Carbon::parse('2026-07-02 10:00:00')->timestamp)->save();
    Redirect::errors()->make()->url('/unhandled')->site('default')->count(2)->lastSeenAt(Carbon::parse('2026-07-02 09:00:00')->timestamp)->save();
    Redirect::make()->source('/handled')->destination('/new')->site('default')->save();

    $response = $this->actingAs($this->user)
        ->getJson(cp_route('advanced-seo.redirects.errors.index').'?sort=hits&order=desc')
        ->assertOk();

    $response->assertJsonPath('data.0.url', '/handled')
        ->assertJsonPath('data.0.hits', 5)
        ->assertJsonPath('data.0.handled', true)
        ->assertJsonPath('data.1.url', '/unhandled')
        ->assertJsonPath('data.1.handled', false);
});

it('404s when error logging is disabled', function () {
    config(['advanced-seo.redirects.errors.enabled' => false]);

    $this->actingAs($this->user)
        ->getJson(cp_route('advanced-seo.redirects.errors.index'))
        ->assertNotFound();
});
