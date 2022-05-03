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
            Events\EntryBlueprintFound::class => 'extendBlueprint',
            Events\TermBlueprintFound::class => 'extendBlueprint',
        ];
    }

    public function extendBlueprint(Event $event): void
    {
        if (! $this->shouldExtendBlueprint($event)) {
            return;
        }

        // The data is used to show/hide fields under certain conditions.
        $seoBlueprint = OnPageSeoBlueprint::make()
            ->data($this->getDataFromEvent($event))
            ->items();

        $event->blueprint->ensureFieldsInSection($seoBlueprint, 'SEO');
    }

    protected function shouldExtendBlueprint(Event $event): bool
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
