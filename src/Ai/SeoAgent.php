<?php

namespace Aerni\AdvancedSeo\Ai;

use Illuminate\Support\Collection;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Statamic\Facades\Site;
use Statamic\Fields\Blueprint;
use Statamic\Fields\Field;
use Statamic\Modifiers\CoreModifiers;
use Statamic\Support\Str;

#[UseCheapestModel]
class SeoAgent implements Agent
{
    use Promptable;

    public function __construct(
        protected string $field,
        protected Blueprint $blueprint,
        protected array|Collection $content,
        protected string $site,
        protected ?string $additionalInstructions = null,
    ) {
        $this->processContent();
    }

    /** @return list<FieldSpec> */
    public static function fields(): array
    {
        return [
            new FieldSpec('seo_title', 'meta title for search engine results', 60),
            new FieldSpec('seo_description', 'meta description for search engine results', 155),
            new FieldSpec('seo_og_title', 'title for social media sharing', 60),
            new FieldSpec('seo_og_description', 'description for social media sharing', 155),
        ];
    }

    public function instructions(): string
    {
        return collect([
            'You are an SEO specialist. Analyze the content below and write compelling, accurate, and optimized SEO text. Follow the provided instructions and rules.',
            $this->contentSection(),
            $this->additionalInstructionsSection(),
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

    protected function processContent(): void
    {
        $content = collect($this->content)->except($this->field);

        $seoFields = $content
            ->only(array_column(self::fields(), 'handle'))
            ->map(fn (array $value) => $value['value'])
            ->map(fn ($value) => trim(preg_replace('/\{\{.*?\}\}/', '', $value)));

        $this->content = $this->blueprint->fields()->all()
            ->filter(fn (Field $field) => in_array('text', $field->fieldtype()->categories()))
            ->map(fn (Field $field) => $this->toPlainText($field->type(), $content->get($field->handle())))
            ->merge($seoFields)
            ->filter();
    }

    protected function contentLength(): int
    {
        $text = preg_replace('/\{\{[^}]*\}\}/', '', $this->content->implode(' '));

        return Str::length(trim($text));
    }

    protected function toPlainText(string $type, mixed $value): ?string
    {
        return match ($type) {
            'bard' => (new CoreModifiers)->bardText($value),
            default => is_string($value) ? $value : null,
        };
    }

    protected function fieldSpec(): FieldSpec
    {
        return collect(self::fields())->firstWhere('handle', $this->field);
    }

    protected function contentSection(): string
    {
        $limit = (int) floor(5000 / $this->content->count());

        $content = $this->content->map(fn (string $value, string $key) => "{$key}: ".Str::limit($value, $limit));

        return collect(['## Content'])
            ->merge($content)
            ->implode("\n");
    }

    protected function additionalInstructionsSection(): ?string
    {
        if ($this->additionalInstructions === null) {
            return null;
        }

        return "## Instructions\n{$this->additionalInstructions}";
    }

    protected function rulesSection(): string
    {
        $rules = collect([
            "Stay within {$this->fieldSpec()->characters} characters. Use the full space to write something compelling and don't be too short.",
            'SEO fields come in pairs: seo_title + seo_description (search engines), and seo_og_title + seo_og_description (social sharing). If the other field in your pair is present in the content, complement it — avoid repeating the same phrasing and ensure the title and description read as a cohesive unit.',
            'Write in '.locale_get_display_language(Site::get($this->site)->locale()).'.',
            'Output only plain text. No explanations, wrapping, or markdown formatting.',
        ]);

        return collect(['## Rules'])
            ->merge($rules->map(fn (string $rule) => "- {$rule}"))
            ->implode("\n");
    }
}
