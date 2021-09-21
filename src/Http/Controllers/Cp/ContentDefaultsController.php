<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Illuminate\Http\Request;
use Statamic\Entries\Collection;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Taxonomies\Taxonomy;

abstract class ContentDefaultsController extends CpController
{
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
            'title' => "Defaults for {$item->title()} {$this->itemType($item)}",
            'action' => cp_route("advanced-seo.content.{$repository->contentType}.update", $item),
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

    protected function itemType($item): string
    {
        if ($item instanceof Collection) {
            return 'Collection';
        }

        if ($item instanceof Taxonomy) {
            return 'Taxonomy';
        }
    }
}
