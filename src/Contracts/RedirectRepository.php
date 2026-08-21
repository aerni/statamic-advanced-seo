<?php

namespace Aerni\AdvancedSeo\Contracts;

use Illuminate\Support\Collection;

interface RedirectRepository
{
    public function make(): Redirect;

    public function find(string $id): ?Redirect;

    public function all(): Collection;

    public function query(): RedirectQueryBuilder;

    public function save(Redirect $redirect): void;

    public function delete(Redirect $redirect): void;

    public static function bindings(): array;
}
