<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Contracts\SeoSetConfig;
use Aerni\AdvancedSeo\Contracts\SeoSetGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Statamic\CP\PublishForm;
use Statamic\Fields\Blueprint;
use Statamic\Http\Controllers\CP\CpController;

class SeoSetConfigController extends CpController
{
    public function edit(SeoSetGroup $seoSetGroup, SeoSet $seoSet): PublishForm
    {
        $this->authorize('configure', [SeoSet::class, $seoSet]);

        $config = $seoSet->config();

        return PublishForm::make(static::editFormBlueprint($config))
            ->parent($seoSet)
            ->asConfig()
            ->icon('cog')
            ->title("Configure {$seoSet->title()}")
            ->values([
                'enabled' => $config->enabled(),
                'origins' => $config->origins(),
            ])
            ->submittingTo($config->editUrl());
    }

    public function update(Request $request, SeoSetGroup $seoSetGroup, SeoSet $seoSet): void
    {
        $this->authorize('configure', [SeoSet::class, $seoSet]);

        $config = $seoSet->config();

        $values = PublishForm::make(static::editFormBlueprint($config))
            ->submit($request->all());

        if ($seoSet->type() !== 'site') {
            $config->enabled(Arr::get($values, 'enabled'));
        }

        $config
            ->origins(Arr::get($values, 'origins'))
            ->seoSet()
            ->save();
    }

    public static function editFormBlueprint(SeoSetConfig $config): Blueprint
    {
        $fields = [];

        if ($config->type() !== 'site') {
            $fields['enabled'] = [
                'display' => __('Enabled'),
                'fields' => [
                    'enabled' => [
                        'display' => __('Enabled'),
                        'instructions' => __('Choose to enable/disable SEO processing for this item.'),
                        'type' => 'toggle',
                        'default' => true,
                    ],
                ],
            ];
        }

        $fields['origins'] = [
            'display' => __('Origins'),
            'fields' => [
                'origins' => [
                    'display' => __('Origins'),
                    'instructions' => __('Choose to inherit values from the selected origin.'),
                    'type' => 'default_set_sites',
                    'if' => array_filter([
                        'enabled' => 'true',
                    ], fn () => $config->type() !== 'site'),
                ],
            ],
        ];

        return \Statamic\Facades\Blueprint::make()
            ->setContents(collect([
                'tabs' => [
                    'main' => [
                        'sections' => collect($fields)->map(fn ($section) => [
                            'display' => $section['display'],
                            'instructions' => $section['instructions'] ?? null,
                            'fields' => collect($section['fields'])->map(fn ($field, $handle) => [
                                'handle' => $handle,
                                'field' => $field,
                            ])->values()->all(),
                        ])->values()->all(),
                    ],
                ],
            ])
                ->all());
    }
}
