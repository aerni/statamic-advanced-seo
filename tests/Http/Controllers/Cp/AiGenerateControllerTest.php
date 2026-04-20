<?php

use Aerni\AdvancedSeo\Tests\Concerns\FakesComposerLock;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class, FakesComposerLock::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);

    $this->installAiPackage();

    config(['ai.default' => 'openai', 'ai.providers.openai.key' => 'test-key']);

    Collection::make('pages')->routes('/{slug}')->sites(['english'])->saveQuietly();

    $this->user = User::make()->makeSuper()->save();
});

it('returns 404 when ai is disabled', function () {
    config(['advanced-seo.ai.enabled' => false]);

    $this->actingAs($this->user)
        ->postJson(cp_route('advanced-seo.ai.generate'), [
            'field' => 'seo_title',
            'blueprint' => 'collections.pages.page',
            'site' => 'english',
            'content' => ['title' => 'Test'],
            'tokens' => [],
        ])
        ->assertNotFound();
});

it('validates field is required', function () {
    config(['advanced-seo.ai.enabled' => true]);

    $this->actingAs($this->user)
        ->postJson(cp_route('advanced-seo.ai.generate'), [
            'blueprint' => 'collections.pages.page',
            'site' => 'english',
            'content' => ['title' => 'Test'],
            'tokens' => [],
        ])
        ->assertJsonValidationErrors('field');
});

it('validates field must be a valid value', function () {
    config(['advanced-seo.ai.enabled' => true]);

    $this->actingAs($this->user)
        ->postJson(cp_route('advanced-seo.ai.generate'), [
            'field' => 'invalid',
            'blueprint' => 'collections.pages.page',
            'site' => 'english',
            'content' => ['title' => 'Test'],
            'tokens' => [],
        ])
        ->assertJsonValidationErrors('field');
});

it('validates blueprint is required', function () {
    config(['advanced-seo.ai.enabled' => true]);

    $this->actingAs($this->user)
        ->postJson(cp_route('advanced-seo.ai.generate'), [
            'field' => 'seo_title',
            'site' => 'english',
            'content' => ['title' => 'Test'],
            'tokens' => [],
        ])
        ->assertJsonValidationErrors('blueprint');
});

it('validates content is required', function () {
    config(['advanced-seo.ai.enabled' => true]);

    $this->actingAs($this->user)
        ->postJson(cp_route('advanced-seo.ai.generate'), [
            'field' => 'seo_title',
            'blueprint' => 'collections.pages.page',
            'site' => 'english',
            'tokens' => [],
        ])
        ->assertJsonValidationErrors('content');
});

it('validates site is required', function () {
    config(['advanced-seo.ai.enabled' => true]);

    $this->actingAs($this->user)
        ->postJson(cp_route('advanced-seo.ai.generate'), [
            'field' => 'seo_title',
            'blueprint' => 'collections.pages.page',
            'content' => ['title' => 'Test'],
            'tokens' => [],
        ])
        ->assertJsonValidationErrors('site');
});

it('rejects blueprint strings with the wrong number of segments', function () {
    config(['advanced-seo.ai.enabled' => true]);

    $this->actingAs($this->user)
        ->postJson(cp_route('advanced-seo.ai.generate'), [
            'field' => 'seo_title',
            'blueprint' => 'collections.pages',
            'site' => 'english',
            'content' => ['title' => 'Test'],
            'tokens' => [],
        ])
        ->assertJsonValidationErrors('blueprint');
});

it('rejects blueprint strings with an unknown type', function () {
    config(['advanced-seo.ai.enabled' => true]);

    $this->actingAs($this->user)
        ->postJson(cp_route('advanced-seo.ai.generate'), [
            'field' => 'seo_title',
            'blueprint' => 'globals.site.page',
            'site' => 'english',
            'content' => ['title' => 'Test'],
            'tokens' => [],
        ])
        ->assertJsonValidationErrors('blueprint');
});

it('returns 404 when the referenced collection does not exist', function () {
    config(['advanced-seo.ai.enabled' => true]);

    $this->actingAs($this->user)
        ->postJson(cp_route('advanced-seo.ai.generate'), [
            'field' => 'seo_title',
            'blueprint' => 'collections.nonexistent.page',
            'site' => 'english',
            'content' => ['title' => 'Test'],
            'tokens' => [],
        ])
        ->assertNotFound();
});
