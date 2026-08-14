<?php

use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesRedirects;
use Aerni\AdvancedSeo\Tests\Concerns\FakesComposerLock;
use Illuminate\Support\Facades\Storage;
use Statamic\Facades\Collection;
use Statamic\Facades\Role;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class, FakesComposerLock::class, EnablesRedirects::class);

beforeEach(function () {
    Site::setSites(['default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en']]);
    Collection::make('pages')->routes('/{slug}')->sites(['default'])->saveQuietly();
    $this->installSimpleExcelPackage();
    Storage::fake('local');
    $this->super = tap(User::make()->makeSuper())->save();
});

function uploadImport(string $content, string $filename): void
{
    Storage::disk('local')->put("statamic/file-uploads/{$filename}", $content);
}

it('imports a file and returns the result as json', function () {
    uploadImport("source,destination,site\n/old,/new,default\n/another,/target,default", 'import.csv');

    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.import'), ['file' => ['import.csv']])
        ->assertOk()
        ->assertJson(['imported' => 2, 'errors' => []]);

    expect(Redirect::query()->where('source', '/old')->first())->not->toBeNull();
});

it('returns the row errors as json without importing anything', function () {
    uploadImport("source,destination,site\n,/new,default\n/valid,/target,default", 'import.csv');

    $response = $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.import'), ['file' => ['import.csv']])
        ->assertOk()
        ->assertJson(['imported' => 0]);

    expect($response->json('errors'))->toHaveCount(1);
    expect(Redirect::query()->count())->toBe(0);
});

it('renders a structural file error as a 422', function () {
    uploadImport("url,target\n/old,/new", 'import.csv');

    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.import'), ['file' => ['import.csv']])
        ->assertStatus(422);
});

it('deletes the uploaded file after import', function () {
    uploadImport("source,destination,site\n/old,/new,default", 'import.csv');

    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.import'), ['file' => ['import.csv']])
        ->assertOk();

    Storage::disk('local')->assertMissing('statamic/file-uploads/import.csv');
});

it('returns 404 when simple-excel is not installed', function () {
    $this->uninstallPackages();
    uploadImport("source,destination\n/old,/new", 'import.csv');

    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.import'), ['file' => ['import.csv']])
        ->assertNotFound();
});

it('forbids a user without the manage permission', function () {
    tap(Role::make('viewer')->addPermission(['access cp', 'access default site']))->save();
    $viewer = tap(User::make()->assignRole('viewer'))->save();
    uploadImport("source,destination\n/old,/new", 'import.csv');

    $this->actingAs($viewer)
        ->postJson(cp_route('advanced-seo.redirects.import'), ['file' => ['import.csv']])
        ->assertForbidden();
});
