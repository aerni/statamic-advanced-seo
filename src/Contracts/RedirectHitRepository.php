<?php

namespace Aerni\AdvancedSeo\Contracts;

use Illuminate\Support\Collection;

interface RedirectHitRepository
{
    public function make(): RedirectHit;

    public function find(string $redirect): ?RedirectHit;

    public function all(): Collection;

    public function query(): RedirectHitQueryBuilder;

    public function save(RedirectHit $hit): void;

    public function delete(RedirectHit $hit): void;

    public static function bindings(): array;
}
