<?php

use Aerni\AdvancedSeo\Contracts\RedirectErrorRepository as RedirectErrorRepositoryContract;
use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Stache\Repositories\RedirectErrorRepository as StacheRedirectErrorRepository;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

it('bulk deletes errors for the given sites', function () {
    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
        'fr' => ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr'],
    ]);

    Redirect::errors()->make()->url('/a')->site('default')->count(1)->save();
    Redirect::errors()->make()->url('/b')->site('default')->count(1)->save();
    Redirect::errors()->make()->url('/c')->site('fr')->count(1)->save();

    Redirect::errors()->deleteBySites(['default']);

    $urls = Redirect::errors()->all()->map->url();

    expect($urls)->not->toContain('/a')->not->toContain('/b')->toContain('/c');
});

it('no-ops when deleteBySites receives an empty site list', function () {
    Redirect::errors()->make()->url('/a')->site('default')->count(1)->save();

    Redirect::errors()->deleteBySites([]);

    expect(Redirect::errors()->all())->toHaveCount(1);
});

it('bulk deletes errors by id', function () {
    $a = tap(Redirect::errors()->make()->url('/a')->site('default')->count(1))->save();
    $b = tap(Redirect::errors()->make()->url('/b')->site('default')->count(1))->save();
    $c = tap(Redirect::errors()->make()->url('/c')->site('default')->count(1))->save();

    Redirect::errors()->deleteByIds([$a->id(), $c->id()]);

    $urls = Redirect::errors()->all()->map->url();

    expect($urls)->not->toContain('/a')->toContain('/b')->not->toContain('/c');
});

it('no-ops when deleteByIds receives an empty id list', function () {
    Redirect::errors()->make()->url('/a')->site('default')->count(1)->save();

    $repo = redirectErrorRepositoryProbe();

    $repo->deleteByIds([]);

    expect(Redirect::errors()->all())->toHaveCount(1)
        ->and($repo->locks)->toBe(0);
});

it('releases the redirect error lock between delete chunks', function () {
    $keep = Redirect::errors()->make()->url('/keep')->site('default')->count(5);
    $keep->save();

    $ids = collect(range(1, 101))->map(function ($i) {
        $error = Redirect::errors()->make()->url("/old-{$i}")->site('default')->count(1);
        $error->save();

        return $error->id();
    })->all();

    $repo = redirectErrorRepositoryProbe();

    $repo->beforeLock = function (int $locks) {
        if ($locks === 2) {
            Redirect::errors()->make()->url('/during')->site('default')->count(1)->save();
        }
    };

    $repo->deleteByIds($ids);

    $urls = Redirect::errors()->all()->map->url();

    expect($repo->locks)->toBe(2)
        ->and($urls)->toContain('/keep')->toContain('/during')
        ->and($urls)->not->toContain('/old-1')->not->toContain('/old-101')
        ->and(Redirect::errors()->all())->toHaveCount(2);
});

it('returns the configured max records', function () {
    config(['advanced-seo.redirects.errors.max_records' => 50]);

    expect(Redirect::errors()->maxRecords())->toBe(50);
});

it('treats false max_records as no cap', function () {
    config(['advanced-seo.redirects.errors.max_records' => false]);

    expect(Redirect::errors()->maxRecords())->toBeNull();
});

it('clamps non-positive max_records to 1', function () {
    config(['advanced-seo.redirects.errors.max_records' => 0]);

    expect(Redirect::errors()->maxRecords())->toBe(1);
});

it('returns the configured purge days', function () {
    config(['advanced-seo.redirects.errors.purge_after_days' => 7]);

    expect(Redirect::errors()->purgeAfterDays())->toBe(7);
});

it('treats false purge_after_days as no age-out', function () {
    config(['advanced-seo.redirects.errors.purge_after_days' => false]);

    expect(Redirect::errors()->purgeAfterDays())->toBeNull();
});

function redirectErrorRepositoryProbe(): StacheRedirectErrorRepository
{
    $repo = new class(app('stache')) extends StacheRedirectErrorRepository
    {
        public int $locks = 0;

        public $beforeLock = null;

        protected function lock(callable $callback): mixed
        {
            $this->locks++;

            if ($this->beforeLock) {
                ($this->beforeLock)($this->locks);
            }

            return parent::lock($callback);
        }
    };

    app()->instance(RedirectErrorRepositoryContract::class, $repo);

    return $repo;
}
