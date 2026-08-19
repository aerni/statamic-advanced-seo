<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Contracts\RedirectErrorRepository;
use Aerni\AdvancedSeo\Contracts\RedirectHitRepository;
use Aerni\AdvancedSeo\Contracts\RedirectRepository;
use Aerni\AdvancedSeo\Enums\RedirectExportFormat;
use Illuminate\Support\Traits\ForwardsCalls;

class RedirectService
{
    use ForwardsCalls;

    public function __construct(protected RedirectRepository $repository) {}

    public function hits(): RedirectHitRepository
    {
        return app(RedirectHitRepository::class);
    }

    public function errors(): RedirectErrorRepository
    {
        return app(RedirectErrorRepository::class);
    }

    public function import(string $path): ImportResult
    {
        return app(RedirectImporter::class)->import($path);
    }

    public function export(RedirectExportFormat $format = RedirectExportFormat::Csv): string
    {
        return app(RedirectExporter::class)->export($format);
    }

    public function resolve(string $path, string $site): ?ResolvedRedirect
    {
        return RedirectResolver::resolve($path, $site);
    }

    public function __call(string $method, array $parameters): mixed
    {
        return $this->forwardCallTo($this->repository, $method, $parameters);
    }
}
