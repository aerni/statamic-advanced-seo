<?php

namespace Aerni\AdvancedSeo\Contracts;

use Aerni\AdvancedSeo\Enums\Origin;
use Aerni\AdvancedSeo\Enums\ResponseCode;
use Aerni\AdvancedSeo\Enums\SourceType;

interface Redirect
{
    public function id(?string $id = null): string|self;

    public function source(?string $source = null): string|self|null;

    public function destination(?string $destination = null): string|self|null;

    public function responseCode(?ResponseCode $responseCode = null): ResponseCode|self;

    public function sourceType(): SourceType;

    public function resolves(): bool;

    public function site(?string $site = null): string|self;

    public function enabled(?bool $enabled = null): bool|self;

    public function preserveQueryString(?bool $preserveQueryString = null): bool|self;

    public function origin(?Origin $origin = null): Origin|self;

    public function description(?string $description = null): string|self|null;

    public function createdAt(?int $createdAt = null): int|self|null;

    public function createdAtIso(): ?string;

    public function sourceUrl(): ?string;

    public function hit(): ?RedirectHit;

    public function editUrl(): string;

    public function path(): string;

    public function fileData(): array;

    public function save(): self;

    public function delete(): bool;
}
