<?php

namespace Aerni\AdvancedSeo\GraphQL\Fields;

use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\GraphQL\Types\ContentDefaultsType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Field;
use Statamic\Facades\GraphQL;

class ContentDefaultsField extends Field
{
    protected $attributes = [
        'description' => 'The Advanced SEO collection or taxonomy defaults',
    ];

    public function args(): array
    {
        return [
            'handle' => [
                'name' => 'handle',
                'type' => GraphQL::string(),
                'rules' => ['required'],
            ],
            'site' => [
                'type' => GraphQL::string(),
            ],
        ];
    }

    public function type(): Type
    {
        return GraphQL::type(ContentDefaultsType::NAME);
    }

    protected function resolve($root, $args, $context, ResolveInfo $info): ?SeoVariables
    {
        $set = Seo::find(str_plural($info->fieldName), $args['handle']);

        if (! $set) {
            return null;
        }

        if (! $set->isEnabled()) {
            return null;
        }

        return array_has($args, 'site')
            ? $set->in($args['site'])
            : $set->inDefaultSite();
    }
}
