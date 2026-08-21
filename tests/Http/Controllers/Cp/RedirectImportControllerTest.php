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

function uploadImport(string $content, string $filename): string
{
    $path = now()->timestamp."/{$filename}";

    Storage::disk('local')->put("statamic/file-uploads/{$path}", $content);

    return $path;
}

it('imports a file and returns the result as json', function () {
    $upload = uploadImport("source,destination,site\n/old,/new,default\n/another,/target,default", 'import.csv');

    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.import'), ['file' => [$upload]])
        ->assertOk()
        ->assertJson(['imported' => 2, 'errors' => []]);

    expect(Redirect::query()->where('source', '/old')->first())->not->toBeNull();
});

it('accepts an import file inside the temporary upload directory regardless of its token shape', function () {
    Storage::disk('local')->put('statamic/file-uploads/import.csv', "source,destination,site\n/old,/new,default");

    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.import'), ['file' => ['import.csv']])
        ->assertOk()
        ->assertJson(['imported' => 1, 'errors' => []]);
});

it('returns the row errors as json without importing anything', function () {
    $upload = uploadImport("source,destination,site\n,/new,default\n/valid,/target,default", 'import.csv');

    $response = $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.import'), ['file' => [$upload]])
        ->assertOk()
        ->assertJson(['imported' => 0]);

    expect($response->json('errors'))->toHaveCount(1);
    expect(Redirect::query()->count())->toBe(0);
});

it('renders a structural file error as a 422', function () {
    $upload = uploadImport("url,target\n/old,/new", 'import.csv');

    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.import'), ['file' => [$upload]])
        ->assertStatus(422);
});

it('deletes the uploaded file after import', function () {
    $upload = uploadImport("source,destination,site\n/old,/new,default", 'import.csv');

    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.import'), ['file' => [$upload]])
        ->assertOk();

    Storage::disk('local')->assertMissing("statamic/file-uploads/{$upload}");
});

it('rejects upload paths outside the temporary upload directory without deleting them', function () {
    Storage::disk('local')->put('target.json', '[{"source":"/old","destination":"/new","site":"default"}]');
    Storage::disk('local')->makeDirectory('statamic/file-uploads');

    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.import'), ['file' => ['../../target.json']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file.0');

    Storage::disk('local')->assertExists('target.json');
    expect(Redirect::query()->count())->toBe(0);
});

it('rejects malformed upload tokens before resolving the filesystem path', function () {
    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.import'), ['file' => ["1750000000/import\0.csv"]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file.0');
});

it('returns 404 when simple-excel is not installed', function () {
    $this->uninstallPackages();
    $upload = uploadImport("source,destination\n/old,/new", 'import.csv');

    $this->actingAs($this->super)
        ->postJson(cp_route('advanced-seo.redirects.import'), ['file' => [$upload]])
        ->assertNotFound();
});

it('forbids a user without the manage permission', function () {
    tap(Role::make('viewer')->addPermission(['access cp', 'access default site']))->save();
    $viewer = tap(User::make()->assignRole('viewer'))->save();
    $upload = uploadImport("source,destination\n/old,/new", 'import.csv');

    $this->actingAs($viewer)
        ->postJson(cp_route('advanced-seo.redirects.import'), ['file' => [$upload]])
        ->assertForbidden();
});
