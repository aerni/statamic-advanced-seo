<?php

use Aerni\AdvancedSeo\Ai\SeoAgent;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);

    config([
        'advanced-seo.ai.enabled' => true,
        'ai.default' => 'openai',
        'ai.providers.openai.key' => 'test-key',
    ]);

    Collection::make('pages')->routes('/{slug}')->sites(['english'])->saveQuietly();
});

it('returns 403 when user lacks seo.edit-content permission', function () {
    $user = User::make()->save();

    $this->actingAs($user)
        ->postJson(cp_route('advanced-seo.ai.generate'), [
            'field' => 'seo_title',
            'blueprint' => 'collections.pages.default',
            'site' => 'english',
            'content' => ['title' => 'Test'],
        ])
        ->assertForbidden();
});

it('resolves taxonomy blueprints', function () {
    \Statamic\Facades\Taxonomy::make('tags')->sites(['english'])->saveQuietly();

    $user = User::make()->makeSuper()->save();

    // This should not return a validation error — it should resolve the blueprint.
    // It will fail at generation (no AI SDK), but won't be a 404 or validation error.
    $this->actingAs($user)
        ->postJson(cp_route('advanced-seo.ai.generate'), [
            'field' => 'seo_title',
            'blueprint' => 'taxonomies.tags.default',
            'site' => 'english',
            'content' => ['title' => str_repeat('Enough content for validation. ', 5)],
        ])
        ->assertStatus(503);
});

it('returns 422 when content is insufficient', function () {
    $user = User::make()->makeSuper()->save();

    // Mock the SeoAgent to throw a RuntimeException (insufficient content).
    $this->mock(SeoAgent::class, function ($mock) {
        $mock->shouldReceive('generate')->andThrow(new RuntimeException('Not enough content'));
    });

    // Use minimal content that passes the request validation but triggers the agent's content check.
    $this->actingAs($user)
        ->postJson(cp_route('advanced-seo.ai.generate'), [
            'field' => 'seo_title',
            'blueprint' => 'collections.pages.default',
            'site' => 'english',
            'content' => ['title' => 'Short'],
        ])
        ->assertStatus(422)
        ->assertJsonStructure(['error']);
});

it('returns 503 when generation throws an unexpected error', function () {
    $user = User::make()->makeSuper()->save();

    // The agent will fail because no AI SDK is actually configured — this triggers the Throwable catch.
    $this->actingAs($user)
        ->postJson(cp_route('advanced-seo.ai.generate'), [
            'field' => 'seo_title',
            'blueprint' => 'collections.pages.default',
            'site' => 'english',
            'content' => ['title' => str_repeat('Enough content for validation. ', 5)],
        ])
        ->assertStatus(503)
        ->assertJsonStructure(['error']);
});

it('includes reason in debug mode on 503', function () {
    config(['app.debug' => true]);

    $user = User::make()->makeSuper()->save();

    $this->actingAs($user)
        ->postJson(cp_route('advanced-seo.ai.generate'), [
            'field' => 'seo_title',
            'blueprint' => 'collections.pages.default',
            'site' => 'english',
            'content' => ['title' => str_repeat('Enough content for validation. ', 5)],
        ])
        ->assertStatus(503)
        ->assertJsonStructure(['error', 'reason']);
});

it('excludes reason when not in debug mode on 503', function () {
    config(['app.debug' => false]);

    $user = User::make()->makeSuper()->save();

    $this->actingAs($user)
        ->postJson(cp_route('advanced-seo.ai.generate'), [
            'field' => 'seo_title',
            'blueprint' => 'collections.pages.default',
            'site' => 'english',
            'content' => ['title' => str_repeat('Enough content for validation. ', 5)],
        ])
        ->assertStatus(503)
        ->assertJsonMissing(['reason']);
});
