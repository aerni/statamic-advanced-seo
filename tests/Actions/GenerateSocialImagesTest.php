<?php

use Aerni\AdvancedSeo\Actions\Statamic\GenerateSocialImages;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);

    Collection::make('pages')->routes('/{slug}')->sites(['english'])->saveQuietly();

    $this->action = new GenerateSocialImages;
});

it('denies authorization when the item SeoSet is not editable', function () {
    Seo::find('collections::pages')->config()->editable(false)->save();

    $user = tap(User::make()->makeSuper())->save();
    $entry = tap(Entry::make()->collection('pages')->locale('english')->slug('about'))->save();

    $this->actingAs($user);

    expect($this->action->authorize($user, $entry))->toBeFalse();
});

it('allows authorization when the item SeoSet is editable and the user can edit', function () {
    $user = tap(User::make()->makeSuper())->save();
    $entry = tap(Entry::make()->collection('pages')->locale('english')->slug('about'))->save();

    $this->actingAs($user);

    expect($this->action->authorize($user, $entry))->toBeTrue();
});
