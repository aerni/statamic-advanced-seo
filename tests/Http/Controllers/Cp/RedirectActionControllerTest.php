<?php

use Aerni\AdvancedSeo\Facades\Redirect;
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
    tap(Role::make('action_viewer')->addPermission(['access cp', 'access default site']))->save();

    return tap(User::make()->assignRole('action_viewer'))->save();
}

it('returns 404 for actions on the free edition', function () {
    $redirect = Redirect::make()->source('/a')->destination('/x')->site('default')->save();

    useFreeEdition();

    $this->actingAs($this->super)
        ->post(cp_route('advanced-seo.redirects.actions.run'), [
            'action' => 'delete_redirect',
            'selections' => [$redirect->id()],
            'values' => [],
        ])->assertNotFound();
});

it('lists the available bulk actions for selected redirects', function () {
    $redirect = Redirect::make()->source('/a')->destination('/x')->site('default')->save();

    $handles = collect($this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.actions.bulk'), ['selections' => [$redirect->id()]])
        ->json())->pluck('handle');

    expect($handles)->toContain('delete_redirect');
});

it('deletes the selected redirects', function () {
    $a = Redirect::make()->source('/a')->destination('/x')->site('default')->save();
    $b = Redirect::make()->source('/b')->destination('/y')->site('default')->save();

    $this->actingAs($this->super)
        ->post(cp_route('advanced-seo.redirects.actions.run'), [
            'action' => 'delete_redirect',
            'selections' => [$a->id(), $b->id()],
            'values' => [],
        ])->assertOk();

    expect(Redirect::query()->get())->toHaveCount(0);
});

it('redirects to the index after deleting from the edit view', function () {
    $redirect = Redirect::make()->source('/a')->destination('/x')->site('default')->save();

    $this->actingAs($this->super)
        ->post(cp_route('advanced-seo.redirects.actions.run'), [
            'action' => 'delete_redirect',
            'selections' => [$redirect->id()],
            'values' => [],
            'context' => ['view' => 'form'],
        ])
        ->assertOk()
        ->assertJsonPath('redirect', cp_route('advanced-seo.redirects.index'));
});

it('does not redirect after deleting from the listing', function () {
    $redirect = Redirect::make()->source('/a')->destination('/x')->site('default')->save();

    $this->actingAs($this->super)
        ->post(cp_route('advanced-seo.redirects.actions.run'), [
            'action' => 'delete_redirect',
            'selections' => [$redirect->id()],
            'values' => [],
        ])
        ->assertOk()
        ->assertJsonMissingPath('redirect');
});

it('enables the selected redirects', function () {
    $redirect = Redirect::make()->source('/a')->destination('/x')->site('default')->enabled(false)->save();

    $this->actingAs($this->super)
        ->post(cp_route('advanced-seo.redirects.actions.run'), [
            'action' => 'enable_redirect',
            'selections' => [$redirect->id()],
            'values' => [],
        ])->assertOk();

    expect(Redirect::find($redirect->id())->enabled())->toBeTrue();
});

it('disables the selected redirects', function () {
    $redirect = Redirect::make()->source('/a')->destination('/x')->site('default')->enabled(true)->save();

    $this->actingAs($this->super)
        ->post(cp_route('advanced-seo.redirects.actions.run'), [
            'action' => 'disable_redirect',
            'selections' => [$redirect->id()],
            'values' => [],
        ])->assertOk();

    expect(Redirect::find($redirect->id())->enabled())->toBeFalse();
});

it('forbids a viewer from running the delete action', function () {
    $redirect = Redirect::make()->source('/a')->destination('/x')->site('default')->save();

    $this->actingAs(redirectActionViewer())
        ->post(cp_route('advanced-seo.redirects.actions.run'), [
            'action' => 'delete_redirect',
            'selections' => [$redirect->id()],
            'values' => [],
        ])->assertForbidden();

    expect(Redirect::query()->get())->toHaveCount(1);
});
