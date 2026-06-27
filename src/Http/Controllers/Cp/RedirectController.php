<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Blueprints\RedirectBlueprint;
use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Enums\RedirectType;
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
            ->values([
                'type' => RedirectType::Permanent->value,
                'enabled' => true,
                'site' => Site::selected()->handle(),
            ])
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
}
