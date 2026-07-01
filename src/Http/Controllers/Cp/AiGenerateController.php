<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Ai\SeoAgent;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Ai;
use Aerni\AdvancedSeo\Support\ContentExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Fields\Blueprint;
use Statamic\Http\Controllers\CP\CpController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AiGenerateController extends CpController
{
    public function __invoke(Request $request): JsonResponse
    {
        throw_unless(Ai::enabled(), new NotFoundHttpException);

        $validated = $request->validate([
            'field' => ['required', 'string', Rule::in(array_column(SeoAgent::fields(), 'handle'))],
            'blueprint' => ['required', 'string', 'regex:/^(collections|taxonomies)\.[^.]+\.[^.]+$/'],
            'content' => ['required', 'array'],
            'site' => ['required', 'string', Rule::in(Site::all()->map->handle()->all())],
        ]);

        [$type, $handle, $blueprint] = explode('.', $validated['blueprint']);

        $seoSet = Seo::find("{$type}::{$handle}");

        throw_unless($seoSet, new NotFoundHttpException);

        $this->authorize('seo.edit-content', $seoSet);

        $blueprint = $this->resolveBlueprint($type, $handle, $blueprint);
        $data = Arr::except($validated['content'], $validated['field']);

        try {
            return response()->json(new SeoAgent(
                field: $validated['field'],
                content: (new ContentExtractor(blueprint: $blueprint, content: $data))->run(),
                seoFields: SeoAgent::seoFieldValues($data),
                locale: Site::get($validated['site'])->locale(),
                userInstructions: $this->resolveUserInstructions($type, $handle, $validated['site']),
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
        $parent = match ($type) {
            'collections' => Collection::findByHandle($handle),
            'taxonomies' => Taxonomy::findByHandle($handle),
        };

        throw_unless($parent, new NotFoundHttpException);

        return $type === 'collections'
            ? $parent->entryBlueprint($blueprint)
            : $parent->termBlueprint($blueprint);
    }

    protected function resolveUserInstructions(string $type, string $handle, string $site): array
    {
        return collect([
            Seo::find('site::defaults')->in($site)->value('ai_instructions'),
            Seo::find("{$type}::{$handle}")?->config()->data()->get('ai_instructions'),
        ])->filter()->values()->all();
    }
}
