<?php

namespace Aerni\AdvancedSeo\Contracts;

use Illuminate\Support\Collection;

interface RedirectErrorRepository
{
    public function make(): RedirectError;

    public function find(string $id): ?RedirectError;

    public function findByUrl(string $url, ?string $site = null): ?RedirectError;

    public function all(): Collection;

    public function query(): RedirectErrorQueryBuilder;

    public function save(RedirectError $error): void;

    public function delete(RedirectError $error): void;

    public function record(string $url, string $site): void;

    public function maxRecords(): ?int;

    public function purgeAfterDays(): ?int;

    public static function bindings(): array;
}
