<?php

namespace Aerni\AdvancedSeo\Tokens;

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Data\SeoSetLocalization;
use Aerni\AdvancedSeo\Facades\Token as TokenFacade;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Collection as EntryCollection;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Facades\Blink;
use Statamic\Fields\Blueprint;
use Statamic\Fields\Field;

class Tokens
{
    public function __construct(protected readonly mixed $parent) {}

    public function all(): Collection
    {
        return $this->fieldTokens()->merge($this->valueTokens());
    }

    public function fieldTokens(): Collection
    {
        return Blink::once("advanced-seo.tokens.fields.{$this->parent->id()}", function () {
            return $this->blueprints()
                ->map(fn (Blueprint $blueprint) => $this->tokenizableFields($blueprint))
                ->reduce(fn (mixed $carry, Collection $fields) => $carry ? $carry->intersectByKeys($fields) : $fields)
                ->merge($this->seoFields())
                ->mapInto(FieldToken::class)
                ->sortBy(fn (FieldToken $token) => $token->display());
        });
    }

    public function valueTokens(): Collection
    {
        $fieldTokens = $this->fieldTokens();

        return TokenFacade::registry()->tokens()
            ->reject(fn (ValueToken $token) => $fieldTokens->has($token->handle()))
            ->map(fn (ValueToken $token) => $token->withParent($this->parent))
            ->sortBy(fn (ValueToken $token) => $token->display());
    }

    protected function blueprints(): Collection
    {
        if (! $this->parent instanceof SeoSetLocalization) {
            return collect([$this->blueprint()]);
        }

        $seoSetParent = Context::from($this->parent)->seoSet()->parent();

        return match (true) {
            $seoSetParent instanceof EntryCollection => $seoSetParent->entryBlueprints(),
            $seoSetParent instanceof Taxonomy => $seoSetParent->termBlueprints(),
        };
    }

    protected function blueprint(): Blueprint
    {
        return match (true) {
            $this->parent instanceof EntryCollection => $this->parent->entryBlueprint(request('blueprint')),
            $this->parent instanceof Taxonomy => $this->parent->termBlueprint(request('blueprint')),
            default => $this->parent->blueprint(),
        };
    }

    /**
     * Get the blueprint fields that have a registered token normalizer.
     */
    protected function tokenizableFields(Blueprint $blueprint): Collection
    {
        $normalizers = TokenFacade::registry()->normalizers();

        return $blueprint->fields()->all()
            ->filter(fn (Field $field) => $normalizers->has($field->type()));
    }

    /**
     * Get the SEO fields that support tokens. These are filtered by handle
     * because the on-page seo fieldtype is a wrapper around the actual child
     * fieldtype (e.g. token_input) and has no token normalizer of its own.
     */
    protected function seoFields(): Collection
    {
        $blueprint = $this->parent instanceof SeoSetLocalization
            ? $this->parent->blueprint()
            : $this->blueprint();

        return $blueprint->fields()->all()
            ->only('seo_title', 'seo_description', 'seo_og_title', 'seo_og_description');
    }
}
