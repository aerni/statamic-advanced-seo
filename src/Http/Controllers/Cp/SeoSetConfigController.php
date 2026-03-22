<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\SeoSets\SeoSet;
use Aerni\AdvancedSeo\SeoSets\SeoSetGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Statamic\CP\PublishForm;
use Statamic\Http\Controllers\CP\CpController;

class SeoSetConfigController extends CpController
{
    public function edit(SeoSetGroup $seoSetGroup, SeoSet $seoSet): PublishForm
    {
        $this->authorize('configure', [SeoSet::class, $seoSet]);

        $config = $seoSet->config();

        return PublishForm::make($config->blueprint())
            ->parent($seoSet)
            ->asConfig()
            ->icon('cog')
            ->title(__('advanced-seo::messages.configure_title', ['title' => $seoSet->title()]))
            ->values(array_merge($config->values()->all(), [
                'enabled' => $config->enabled(),
                'origins' => $config->origins(),
            ]))
            ->submittingTo($config->editUrl());
    }

    public function update(Request $request, SeoSetGroup $seoSetGroup, SeoSet $seoSet): void
    {
        $this->authorize('configure', [SeoSet::class, $seoSet]);

        $config = $seoSet->config();

        $values = PublishForm::make($config->blueprint())
            ->submit($request->all());

        if ($seoSet->type() !== 'site') {
            $config->enabled(Arr::get($values, 'enabled'));
        }

        $config
            ->origins(Arr::get($values, 'origins'))
            ->data(Arr::except($values, ['enabled', 'origins']))
            ->save();
    }
}
