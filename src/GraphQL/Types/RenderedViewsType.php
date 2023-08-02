<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\View\GraphQlCascade;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\View\View;
use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;

class RenderedViewsType extends Type
{
    const NAME = 'renderedViews';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The rendered Advanced SEO `head` and `body` views. Only use this when your frontend is hosted on the same domain as Statamic, as the views contain a whole bunch of absolute URLs that won\'t make sense otherwise.',
    ];

    public function fields(): array
    {
        return [
            'head' => [
                'type' => GraphQL::string(),
                'args' => $this->args(),
                'resolve' => $this->resolveHead(),
            ],
            'body' => [
                'type' => GraphQL::string(),
                'resolve' => fn (GraphQlCascade $cascade) => $this->formatView(view('advanced-seo::body', ['seo' => $cascade->toAugmentedArray()])),
            ],
        ];
    }

    private function resolveHead(): callable
    {
        return function (GraphQlCascade $cascade, $args, $context, ResolveInfo $info) {
            $data = $cascade->baseUrl($args['baseUrl'] ?? null)->toAugmentedArray();
            $view = view('advanced-seo::head', ['seo' => $data]);

            return $this->formatView($view);
        };
    }

    private function args(): array
    {
        return [
            'baseUrl' => [
                'name' => 'baseUrl',
                'description' => 'Change the base URL if your frontend is hosted on another domain than Statamic',
                'type' => GraphQL::string(),
                'rules' => ['url'],
            ],
        ];
    }

    protected function formatView(View $view): string
    {
        return preg_replace('/\s+/', ' ', $view->render());
    }
}
