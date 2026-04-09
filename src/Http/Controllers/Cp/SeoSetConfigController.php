<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\SeoSets\SeoSet;
use Aerni\AdvancedSeo\SeoSets\SeoSetGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Statamic\CP\PublishForm;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Http\Controllers\CP\CpController;

class SeoSetConfigController extends CpController
{
    public function edit(SeoSetGroup $seoSetGroup, SeoSet $seoSet): PublishForm
    {
        $this->authorize('configure', [SeoSet::class, $seoSet]);

        $config = $seoSet->config();

        throw_unless($config->blueprint()->fields()->all()->isNotEmpty(), new NotFoundHttpException);

        return PublishForm::make($config->blueprint())
            ->parent($seoSet)
            ->asConfig()
            ->icon('cog')
            ->title(__('advanced-seo::messages.configure_title', ['title' => $seoSet->title()]))
            ->values(array_merge($config->values()->all(), [
                'enabled' => $config->enabled(),
                'editable' => $config->editable(),
                'origins' => $config->origins(),
            ]))
            ->submittingTo($config->editUrl());
    }

    public function update(Request $request, SeoSetGroup $seoSetGroup, SeoSet $seoSet): void
    {
        $this->authorize('configure', [SeoSet::class, $seoSet]);

        $config = $seoSet->config();

        throw_unless($config->blueprint()->fields()->all()->isNotEmpty(), new NotFoundHttpException);

        $values = PublishForm::make($config->blueprint())
            ->submit($request->all());

        if ($seoSet->type() !== 'site') {
            $config->enabled(Arr::get($values, 'enabled'));
            $config->editable(Arr::get($values, 'editable'));
        }

        $config
            ->origins(Arr::get($values, 'origins'))
            ->data(Arr::except($values, ['enabled', 'editable', 'origins']))
            ->save();
    }
}
