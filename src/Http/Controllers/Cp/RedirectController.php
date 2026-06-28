<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Blueprints\RedirectBlueprint;
use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Enums\RedirectType;
use Aerni\AdvancedSeo\Facades\Redirects;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Statamic\CP\PublishForm;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;

class RedirectController extends CpController
{
    public function index()
    {
        $this->authorize('viewAny', Redirect::class);

        return Inertia::render('advanced-seo::Redirects/Index', [
            'title' => __('advanced-seo::messages.redirects'),
            'createUrl' => cp_route('advanced-seo.redirects.create'),
            'listingUrl' => cp_route('advanced-seo.redirects.index'),
            'canCreate' => User::current()->can('create', Redirect::class),
        ]);
    }

    public function create(): PublishForm
    {
        $this->authorize('create', Redirect::class);

        return PublishForm::make(RedirectBlueprint::definition())
            ->title(__('advanced-seo::messages.redirect_create_title'))
            ->submittingTo(cp_route('advanced-seo.redirects.store'), 'POST');
    }

    public function edit(Redirect $redirect): PublishForm
    {
        $this->authorize('edit', $redirect);

        return PublishForm::make(RedirectBlueprint::definition())
            ->title(__('advanced-seo::messages.redirect_edit_title'))
            ->values([
                'source' => $redirect->source(),
                'destination' => $redirect->destination(),
                'type' => $redirect->type()->value,
                'enabled' => $redirect->enabled(),
                'description' => $redirect->description(),
                'site' => $redirect->site(),
            ])
            ->submittingTo(cp_route('advanced-seo.redirects.update', $redirect->id()));
    }

    public function store(Request $request): array
    {
        $this->authorize('create', Redirect::class);

        $values = $this->validateAndProcess($request, null);

        $redirect = $this->fill(Redirects::make(), $values)->save();

        return ['redirect' => $redirect->editUrl()];
    }

    public function update(Request $request, Redirect $redirect): array
    {
        $this->authorize('edit', $redirect);

        $values = $this->validateAndProcess($request, $redirect);

        $this->fill($redirect, $values)->save();

        return ['redirect' => $redirect->editUrl()];
    }

    public function destroy(Redirect $redirect)
    {
        $this->authorize('delete', $redirect);

        $redirect->delete();

        return response('', 200);
    }

    protected function validateAndProcess(Request $request, ?Redirect $redirect): array
    {
        $fields = RedirectBlueprint::definition()->fields()->addValues($request->all());

        $fields->validator()->withReplacements([
            'id' => $redirect?->id(),
            'site' => $request->input('site', Site::default()->handle()),
        ])->validate();

        return $fields->process()->values()->all();
    }

    protected function fill(Redirect $redirect, array $values): Redirect
    {
        return $redirect
            ->source(Arr::get($values, 'source'))
            ->destination(Arr::get($values, 'destination'))
            ->type(RedirectType::from((int) Arr::get($values, 'type', 301)))
            ->enabled((bool) Arr::get($values, 'enabled', true))
            ->description(Arr::get($values, 'description'))
            ->site(Arr::get($values, 'site', Site::selected()->handle()));
    }
}
