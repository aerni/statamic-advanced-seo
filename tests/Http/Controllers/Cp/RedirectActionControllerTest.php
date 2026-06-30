<?php

use Aerni\AdvancedSeo\Facades\Redirects;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites(['default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en']]);
    $this->super = tap(User::make()->makeSuper())->save();
});

function redirectActionViewer()
{
    tap(Role::make('action_viewer')->addPermission(['access cp', 'view redirects', 'access default site']))->save();

    return tap(User::make()->assignRole('action_viewer'))->save();
}

it('lists the available bulk actions for selected redirects', function () {
    $redirect = Redirects::make()->source('/a')->destination('/x')->site('default')->save();

    $handles = collect($this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.actions.bulk'), ['selections' => [$redirect->id()]])
        ->json())->pluck('handle');

    expect($handles)->toContain('delete_redirect');
});

it('deletes the selected redirects', function () {
    $a = Redirects::make()->source('/a')->destination('/x')->site('default')->save();
    $b = Redirects::make()->source('/b')->destination('/y')->site('default')->save();

    $this->actingAs($this->super)
        ->post(cp_route('advanced-seo.redirects.actions.run'), [
            'action' => 'delete_redirect',
            'selections' => [$a->id(), $b->id()],
            'values' => [],
        ])->assertOk();

    expect(Redirects::query()->get())->toHaveCount(0);
});

it('enables the selected redirects', function () {
    $redirect = Redirects::make()->source('/a')->destination('/x')->site('default')->enabled(false)->save();

    $this->actingAs($this->super)
        ->post(cp_route('advanced-seo.redirects.actions.run'), [
            'action' => 'enable_redirect',
            'selections' => [$redirect->id()],
            'values' => [],
        ])->assertOk();

    expect(Redirects::find($redirect->id())->enabled())->toBeTrue();
});

it('disables the selected redirects', function () {
    $redirect = Redirects::make()->source('/a')->destination('/x')->site('default')->enabled(true)->save();

    $this->actingAs($this->super)
        ->post(cp_route('advanced-seo.redirects.actions.run'), [
            'action' => 'disable_redirect',
            'selections' => [$redirect->id()],
            'values' => [],
        ])->assertOk();

    expect(Redirects::find($redirect->id())->enabled())->toBeFalse();
});

it('forbids a viewer from running the delete action', function () {
    $redirect = Redirects::make()->source('/a')->destination('/x')->site('default')->save();

    $this->actingAs(redirectActionViewer())
        ->post(cp_route('advanced-seo.redirects.actions.run'), [
            'action' => 'delete_redirect',
            'selections' => [$redirect->id()],
            'values' => [],
        ])->assertForbidden();

    expect(Redirects::query()->get())->toHaveCount(1);
});
