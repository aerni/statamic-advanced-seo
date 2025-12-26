<?php

namespace Aerni\AdvancedSeo\Contracts;

use Aerni\AdvancedSeo\Data\SeoVariables;
use Illuminate\Support\Collection;

interface SeoVariablesRepository
{
    public function all(): Collection;

    public function find(string $id): ?SeoVariables;

    public function whereSet(string $type, string $handle): Collection;

    public function save(SeoVariables $variables): void;

    public function delete(SeoVariables $variables): void;
}
