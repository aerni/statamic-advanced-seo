<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Ai\SeoAgent;
use Aerni\AdvancedSeo\Features\Ai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Statamic\Facades\Collection;
use Statamic\Facades\Taxonomy;
use Statamic\Fields\Blueprint;
use Statamic\Http\Controllers\CP\CpController;
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

        $validated['blueprint'] = $this->resolveBlueprint($validated['blueprint']);

        try {
            return response()->json(new SeoAgent(...$validated)->generate());
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

    protected function resolveBlueprint(string $handle): Blueprint
    {
        [$type, $handle, $blueprint] = explode('.', $handle);

        return match ($type) {
            'collections' => Collection::findByHandle($handle)->entryBlueprint($blueprint),
            'taxonomies' => Taxonomy::findByHandle($handle)->termBlueprint($blueprint),
        };
    }
}
