@extends('statamic::layout')
@section('title', __('advanced-seo::messages.site'))

@section('content')

    <breadcrumb :title='@json($breadcrumb_title)' :url='@json($breadcrumb_url)'></breadcrumb>

    <div class="mb-3">
        <h1>{{ __('advanced-seo::messages.site') }}</h1>
    </div>

    <div class="flex flex-wrap -mx-2 widgets">
        <div class="w-full px-2 mb-4 widget">
            <div class="p-0 card content">
                <div class="flex flex-wrap p-2">
                    @can('view general defaults')
                        <a href="{{ cp_route('advanced-seo.site.edit', 'general') }}" class="flex items-start w-full p-2 rounded-md lg:w-1/2 hover:bg-blue-100 group">
                            <div class="w-8 h-8 mr-2 text-blue">
                                @cp_svg('sites')
                            </div>
                            <div class="flex-1 mt-sm">
                                <h2>{{ __('advanced-seo::messages.general') }}</h2>
                                <p>{{ __('advanced-seo::messages.general_description') }}</p>
                            </div>
                        </a>
                    @endcan
                    @can('view indexing defaults')
                        <a href="{{ cp_route('advanced-seo.site.edit', 'indexing') }}" class="flex items-start w-full p-2 rounded-md lg:w-1/2 hover:bg-blue-100 group">
                            <div class="w-8 h-8 mr-2 text-blue">
                                @cp_svg('structures')
                            </div>
                            <div class="flex-1 mt-sm">
                                <h2>{{ __('advanced-seo::messages.indexing') }}</h2>
                                <p>{{ __('advanced-seo::messages.indexing_description') }}</p>
                            </div>
                        </a>
                    @endcan
                    @can('view social_media defaults')
                        <a href="{{ cp_route('advanced-seo.site.edit', 'social_media') }}" class="flex items-start w-full p-2 rounded-md lg:w-1/2 hover:bg-blue-100 group">
                            <div class="w-8 h-8 mr-2 text-blue">
                                @cp_svg('assets')
                            </div>
                            <div class="flex-1 mt-sm">
                                <h2>{{ __('advanced-seo::messages.social_media') }}</h2>
                                <p>{{ __('advanced-seo::messages.social_media_description') }}</p>
                            </div>
                        </a>
                    @endcan
                    @if (! empty(array_filter(config('advanced-seo.analytics'))))
                        @can('view analytics defaults')
                            <a href="{{ cp_route('advanced-seo.site.edit', 'analytics') }}" class="flex items-start w-full p-2 rounded-md lg:w-1/2 hover:bg-blue-100 group">
                                <div class="w-8 h-8 mr-2 text-blue">
                                    @cp_svg('charts')
                                </div>
                                <div class="flex-1 mt-sm">
                                    <h2>{{ __('advanced-seo::messages.analytics') }}</h2>
                                    <p>{{ __('advanced-seo::messages.analytics_description') }}</p>
                                </div>
                            </a>
                        @endcan
                    @endif
                    @if (config('advanced-seo.favicons.enabled', false))
                        @can('view favicons defaults')
                            <a href="{{ cp_route('advanced-seo.site.edit', 'favicons') }}" class="flex items-start w-full p-2 rounded-md lg:w-1/2 hover:bg-blue-100 group">
                                <div class="w-8 h-8 mr-2 text-blue">
                                    @cp_svg('color')
                                </div>
                                <div class="flex-1 mt-sm">
                                    <h2>{{ __('advanced-seo::messages.favicons') }}</h2>
                                    <p>{{ __('advanced-seo::messages.favicons_description') }}</p>
                                </div>
                            </a>
                        @endcan
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('advanced-seo::cp/_docs_callout')

@endsection
