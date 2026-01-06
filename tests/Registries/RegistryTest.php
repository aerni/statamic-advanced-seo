<?php

use Aerni\AdvancedSeo\Registries\Registry;
use Illuminate\Support\Collection;

class TestRegistryOne extends Registry
{
    protected function items(): array
    {
        return ['item1', 'item2', 'item3'];
    }
}

class TestRegistryTwo extends Registry
{
    protected function items(): array
    {
        return ['item1', 'item2'];
    }
}

it('gets all items as collection', function () {
    $result = new TestRegistryOne()->all();

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->all())->toBe(['item1', 'item2', 'item3']);
});

it('caches results using blink', function () {
    $registry = new TestRegistryOne;

    expect($registry->all())->toBe($registry->all());
});

it('refreshes cache after blink flush', function () {
    $registry = new TestRegistryOne;

    $firstCall = $registry->all();

    flushBlink();

    $secondCall = $registry->all();

    expect($firstCall)->not->toBe($secondCall)
        ->and($firstCall->all())->toBe($secondCall->all());
});

it('shares cached results across multiple instances of same registry', function () {
    $registry1 = new TestRegistryOne;
    $registry2 = new TestRegistryOne;

    expect($registry1->all())->toBe($registry2->all());
});

it('uses separate cache keys for different registries', function () {
    $result1 = new TestRegistryOne()->all();
    $result2 = new TestRegistryTwo()->all();

    expect($result1)->not->toBe($result2)
        ->and($result1->count())->toBe(3)
        ->and($result2->count())->toBe(2);
});
