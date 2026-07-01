<?php

use Aerni\AdvancedSeo\Ai\SeoAgent;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesAi;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class, EnablesAi::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);

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
        ])
        ->assertNotFound();
});

it('validates the request', function (callable $mutate, string $error) {
    $payload = $mutate([
        'field' => 'seo_title',
        'blueprint' => 'collections.pages.page',
        'site' => 'english',
        'content' => ['title' => 'Test'],
    ]);

    $this->actingAs($this->user)
        ->postJson(cp_route('advanced-seo.ai.generate'), $payload)
        ->assertJsonValidationErrors($error);
})->with([
    'blueprint must have three segments' => [fn (array $payload) => [...$payload, 'blueprint' => 'collections.pages'], 'blueprint'],
    'blueprint must reference a known type' => [fn (array $payload) => [...$payload, 'blueprint' => 'globals.site.page'], 'blueprint'],
    'site must be a known handle' => [fn (array $payload) => [...$payload, 'site' => 'nonexistent'], 'site'],
]);

it('returns 404 when the referenced collection or taxonomy does not exist', function (string $blueprint) {
    $this->actingAs($this->user)
        ->postJson(cp_route('advanced-seo.ai.generate'), [
            'field' => 'seo_title',
            'blueprint' => $blueprint,
            'site' => 'english',
            'content' => ['title' => 'Test'],
        ])
        ->assertNotFound();
})->with([
    'collection' => 'collections.nonexistent.page',
    'taxonomy' => 'taxonomies.nonexistent.term',
]);

it('returns 403 when the user lacks the seo.edit-content permission', function () {
    $user = User::make()->save();

    $this->actingAs($user)
        ->postJson(cp_route('advanced-seo.ai.generate'), [
            'field' => 'seo_title',
            'blueprint' => 'collections.pages.page',
            'site' => 'english',
            'content' => ['title' => 'Test'],
        ])
        ->assertForbidden();
});

it('generates seo text', function () {
    SeoAgent::fake(['Generated title']);

    $response = $this->actingAs($this->user)
        ->postJson(cp_route('advanced-seo.ai.generate'), [
            'field' => 'seo_title',
            'blueprint' => 'collections.pages.page',
            'site' => 'english',
            'content' => ['title' => str_repeat('Enough content for validation. ', 5)],
        ]);

    $response->assertOk();

    expect($response->json())->toBe('Generated title');
});

it('returns 422 when generation reports insufficient content', function () {
    $this->actingAs($this->user)
        ->postJson(cp_route('advanced-seo.ai.generate'), [
            'field' => 'seo_title',
            'blueprint' => 'collections.pages.page',
            'site' => 'english',
            'content' => ['title' => 'Short'],
        ])
        ->assertStatus(422)
        ->assertJsonStructure(['error']);
});

it('includes the reason in debug mode on 503', function () {
    config(['app.debug' => true]);

    SeoAgent::fake(fn () => throw new Exception('AI provider unavailable'));

    $this->actingAs($this->user)
        ->postJson(cp_route('advanced-seo.ai.generate'), [
            'field' => 'seo_title',
            'blueprint' => 'collections.pages.page',
            'site' => 'english',
            'content' => ['title' => str_repeat('Enough content for validation. ', 5)],
        ])
        ->assertStatus(503)
        ->assertJsonStructure(['error', 'reason']);
});

it('excludes the reason when not in debug mode on 503', function () {
    config(['app.debug' => false]);

    SeoAgent::fake(fn () => throw new Exception('AI provider unavailable'));

    $this->actingAs($this->user)
        ->postJson(cp_route('advanced-seo.ai.generate'), [
            'field' => 'seo_title',
            'blueprint' => 'collections.pages.page',
            'site' => 'english',
            'content' => ['title' => str_repeat('Enough content for validation. ', 5)],
        ])
        ->assertStatus(503)
        ->assertJsonStructure(['error'])
        ->assertJsonMissing(['reason']);
});
