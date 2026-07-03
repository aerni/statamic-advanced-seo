<?php

namespace Aerni\AdvancedSeo\Contracts;

interface RedirectHit
{
    public function redirect(?string $redirect = null): string|self;

    public function count(?int $count = null): int|self;

    public function lastHitAt(?string $lastHitAt = null): string|self|null;

    public function id(): string;

    public function path(): string;

    public function fileData(): array;

    public function save(): self;

    public function delete(): bool;
}
