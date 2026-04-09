<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Ai\SeoAgent;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Ai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Statamic\Facades\Collection;
use Statamic\Facades\Taxonomy;
use Statamic\Fields\Blueprint;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AiGenerateController extends CpController
{
    public function __invoke(Request $request): JsonResponse
    {
        throw_unless(Ai::enabled(), new NotFoundHttpException);

        $this->authorize('seo.edit-content');

        $validated = $request->validate([
            'field' => ['required', 'string', Rule::in(array_column(SeoAgent::fields(), 'handle'))],
            'blueprint' => ['required', 'string'],
            'content' => ['required', 'array'],
            'site' => ['required', 'string'],
        ]);

        [$type, $handle, $blueprint] = explode('.', $validated['blueprint']);

        try {
            return response()->json(new SeoAgent(
                field: $validated['field'],
                blueprint: $this->resolveBlueprint($type, $handle, $blueprint),
                content: $validated['content'],
                site: $validated['site'],
                additionalInstructions: $this->resolveAdditionalInstructions($type, $handle, $validated['site']),
            )->generate());
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(array_filter([
                'error' => __('advanced-seo::messages.ai_generation_failed'),
                'reason' => config('app.debug') ? $e->getMessage() : null,
            ]), 503);
        }
    }

    protected function resolveBlueprint(string $type, string $handle, string $blueprint): Blueprint
    {
        return match ($type) {
            'collections' => Collection::findByHandle($handle)->entryBlueprint($blueprint),
            'taxonomies' => Taxonomy::findByHandle($handle)->termBlueprint($blueprint),
        };
    }

    protected function resolveAdditionalInstructions(string $type, string $handle, string $site): ?string
    {
        $global = Seo::find('site::defaults')->in($site)->value('ai_instructions');
        $scoped = Seo::find("{$type}::{$handle}")?->config()->data()->get('ai_instructions');

        return collect([
            $global ? "### General\n{$global}" : null,
            $scoped ? '### Specific to this '.Str::singular($type)."\n{$scoped}" : null,
        ])->filter()->implode("\n\n") ?: null;
    }
}
