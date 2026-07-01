<?php

namespace Aerni\AdvancedSeo\Ai;

use Illuminate\Support\Collection;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Statamic\Support\Str;

#[UseCheapestModel]
class SeoAgent implements Agent
{
    use Promptable;

    public function __construct(
        protected string $field,
        protected Collection $content,
        protected Collection $seoFields,
        protected string $locale,
        protected array $userInstructions = [],
    ) {}

    /** @return list<FieldSpec> */
    public static function fields(): array
    {
        return [
            new FieldSpec('seo_title', 'meta title for search engine results', 60, 'seo_description'),
            new FieldSpec('seo_description', 'meta description for search engine results', 155, 'seo_title'),
            new FieldSpec('seo_og_title', 'title for social media sharing', 60, 'seo_og_description'),
            new FieldSpec('seo_og_description', 'description for social media sharing', 155, 'seo_og_title'),
        ];
    }

    /**
     * Read the existing custom values of the SEO fields from a raw content payload,
     * stripping Antlers tokens. Fields left on their default are ignored.
     */
    public static function seoFieldValues(array $content): Collection
    {
        return collect($content)
            ->only(array_column(self::fields(), 'handle'))
            ->filter(fn (array $value) => $value['source'] === 'custom')
            ->map(fn (array $value) => (string) $value['value'])
            ->map(fn (string $value) => trim(preg_replace('/\{\{.*?\}\}/', '', $value)))
            ->filter();
    }

    public function instructions(): string
    {
        return collect([
            "You are an SEO specialist. Write the {$this->fieldSpec()->purpose} for this page, based on the content and any SEO fields below. Follow the user instructions and rules.",
            $this->contentSection(),
            $this->seoFieldsSection(),
            $this->userInstructionsSection(),
            $this->rulesSection(),
        ])->filter()->implode("\n\n");
    }

    public function generate(): string
    {
        $missing = 50 - $this->contentLength();

        throw_if($missing > 0, new \RuntimeException(
            __('advanced-seo::messages.ai_insufficient_content', ['characters' => $missing]),
        ));

        $response = $this->prompt(
            "Generate an optimized {$this->fieldSpec()->purpose} for this content.",
            provider: config('advanced-seo.ai.provider'),
            model: config('advanced-seo.ai.model'),
        );

        return $response->text;
    }

    protected function contentLength(): int
    {
        $text = preg_replace('/\{\{[^}]*\}\}/', '', $this->content->merge($this->seoFields)->implode(' '));

        return Str::length(trim($text));
    }

    protected function fieldSpec(): FieldSpec
    {
        return collect(self::fields())->firstWhere('handle', $this->field);
    }

    protected function charLimit(): int
    {
        return (int) floor(5000 / max(1, $this->content->count() + $this->seoFields->count()));
    }

    protected function contentSection(): ?string
    {
        if ($this->content->isEmpty()) {
            return null;
        }

        return "## Content\n".$this->content
            ->map(fn (string $value) => Str::limit($value, $this->charLimit()))
            ->implode("\n\n");
    }

    protected function seoFieldsSection(): ?string
    {
        if ($this->seoFields->isEmpty()) {
            return null;
        }

        return collect(['## SEO fields'])
            ->merge($this->seoFields->map(fn (string $value, string $key) => "{$key}: ".Str::limit($value, $this->charLimit())))
            ->implode("\n");
    }

    protected function userInstructionsSection(): ?string
    {
        if (empty($this->userInstructions)) {
            return null;
        }

        return "## User Instructions\n".collect($this->userInstructions)->implode("\n\n");
    }

    protected function rulesSection(): string
    {
        $sibling = collect(self::fields())->firstWhere('handle', $this->fieldSpec()->sibling);

        $rules = collect([
            "Stay within {$this->fieldSpec()->characters} characters. Use the full space to write something compelling and don't be too short.",
            "This field pairs with the {$sibling->purpose} ({$sibling->handle}). If that field appears under \"SEO fields\", complement it: avoid repeating its phrasing and ensure the two read as a cohesive unit.",
            'Write in '.locale_get_display_language($this->locale).'.',
            'Output only plain text. No explanations, wrapping, or markdown formatting.',
        ]);

        return collect(['## Rules'])
            ->merge($rules->map(fn (string $rule) => "- {$rule}"))
            ->implode("\n");
    }
}
