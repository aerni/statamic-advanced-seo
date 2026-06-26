<?php

namespace Aerni\AdvancedSeo\Redirects;

use Aerni\AdvancedSeo\Contracts\RedirectRepository;
use Illuminate\Support\Traits\ForwardsCalls;

class RedirectService
{
    use ForwardsCalls;

    public function __construct(protected RedirectRepository $repository) {}

    public function resolve(string $path, string $site): ?ResolvedRedirect
    {
        return RedirectResolver::resolve($path, $site);
    }

    public function __call(string $method, array $parameters): mixed
    {
        return $this->forwardCallTo($this->repository, $method, $parameters);
    }
}
