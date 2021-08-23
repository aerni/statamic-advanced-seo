<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Statamic\Facades\Site;
use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\CpController;

abstract class ContentDefaultsController extends CpController
{
    protected string $contentType;

    abstract protected function getContentItem(string $handle);

    abstract protected function getContentRepository(string $handle);

    public function edit(Request $request, string $handle)
    {
        $item = $this->getContentItem($handle);
        $repository = $this->getContentRepository($handle);

        $site = $request->site ?? Site::selected()->handle();

        $collectionDefaults = $repository->get($site)->all();

        $blueprint = $repository->blueprint();

        $fields = $blueprint
            ->fields()
            ->addValues($collectionDefaults)
            ->preProcess();

        return view('advanced-seo::cp/edit', [
            'breadcrumbTitle' => __('advanced-seo::messages.content'),
            'breadcrumbUrl' => cp_route('advanced-seo.content.index'),
            'title' => $item->title().' SEO',
            'action' => cp_route("advanced-seo.content.{$this->contentType}.update", $item),
            'blueprint' => $blueprint->toPublishArray(),
            'meta' => $fields->meta(),
            'values' => $fields->values(),
        ]);
    }

    public function update(string $handle, Request $request)
    {
        $site = $request->site ?? Site::selected()->handle();

        $repository = $this->getContentRepository($handle);
        $blueprint = $repository->blueprint();

        $fields = $blueprint->fields()->addValues($request->all());

        $fields->validate();

        $values = $fields->process()->values();

        $repository->save($site, $values);
    }
}
