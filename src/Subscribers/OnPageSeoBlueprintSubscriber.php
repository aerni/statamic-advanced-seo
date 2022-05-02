<?php

namespace Aerni\AdvancedSeo\Subscribers;

use Aerni\AdvancedSeo\Actions\EvaluateModelHandle;
use Aerni\AdvancedSeo\Actions\EvaluateModelType;
use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Aerni\AdvancedSeo\Concerns\GetsEventData;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;
use Statamic\Events;
use Statamic\Events\Event;

class OnPageSeoBlueprintSubscriber
{
    use GetsEventData;

    public function subscribe(Dispatcher $events): array
    {
        return [
            Events\EntryBlueprintFound::class => 'handleBlueprintFound',
            Events\TermBlueprintFound::class => 'handleBlueprintFound',
        ];
    }

    public function handleBlueprintFound(Event $event): void
    {
        if (! $this->shouldHandleBlueprintFound($event)) {
            return;
        }

        $this->extendBlueprint($event);
    }

    protected function extendBlueprint(Event $event): void
    {
        // The data is used to show/hide fields under certain conditions.
        $seoBlueprint = OnPageSeoBlueprint::make()
            ->data($this->getDataFromEvent($event))
            ->items();

        $this->getBlueprintFromEvent($event)->ensureFieldsInSection($seoBlueprint, 'SEO');
    }

    protected function shouldHandleBlueprintFound(Event $event): bool
    {
        // Don't add any fields in the blueprint builder.
        if (Str::containsAll(request()->path(), [config('statamic.cp.route', 'cp'), 'blueprints'])) {
            return false;
        }

        // Don't add any fields on any addon views in the CP.
        if (Str::containsAll(request()->path(), [config('statamic.cp.route', 'cp'), 'advanced-seo'])) {
            return false;
        }

        $model = EvaluateModelType::handle($event);
        $handle = EvaluateModelHandle::handle($event);

        // Don't add fields if the collection/taxonomy is excluded in the config.
        if (in_array($handle, config("advanced-seo.disabled.{$model}", []))) {
            return false;
        }

        return true;
    }
}
