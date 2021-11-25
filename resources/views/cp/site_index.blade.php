@extends('statamic::layout')
@section('title', __('advanced-seo::messages.site'))

@section('content')

    <div class="mb-3">
        <h1>{{ __('advanced-seo::messages.site') }}</h1>
    </div>

    <div class="flex flex-wrap -mx-2 widgets">
        @can('view general defaults')
            <div class="w-full px-2 mb-4 lg:w-1/2 widget">
                <div class="h-full p-0 overflow-hidden card content">
                    <a href="{{ cp_route('advanced-seo.site.edit', 'general') }}" class="flex items-start h-full p-3 hover:bg-blue-100">
                        <div class="w-8 h-8 mr-3 text-blue">
                            @cp_svg('sites')
                        </div>
                        <div class="flex-1 mt-sm">
                            <h2>{{ __('advanced-seo::messages.general') }}</h2>
                            <p>{{ __('advanced-seo::messages.general_description') }}</p>
                        </div>
                    </a>
                </div>
            </div>
        @endcan
        @can('view marketing defaults')
            <div class="w-full px-2 mb-4 lg:w-1/2 widget">
                <div class="h-full p-0 overflow-hidden card content">
                    <a href="{{ cp_route('advanced-seo.site.edit', 'marketing') }}" class="flex items-start h-full p-3 hover:bg-blue-100">
                        <div class="w-8 h-8 mr-3 text-blue">
                            @cp_svg('charts')
                        </div>
                        <div class="flex-1 mt-sm">
                            <h2>{{ __('advanced-seo::messages.marketing') }}</h2>
                            <p>{{ __('advanced-seo::messages.marketing_description') }}</p>
                        </div>
                    </a>
                </div>
            </div>
        @endcan
        @can('view indexing defaults')
            <div class="w-full px-2 mb-4 lg:w-1/2 widget">
                <div class="h-full p-0 overflow-hidden card content">
                    <a href="{{ cp_route('advanced-seo.site.edit', 'indexing') }}" class="flex items-start h-full p-3 hover:bg-blue-100">
                        <div class="w-8 h-8 mr-3 text-blue">
                            @cp_svg('structures')
                        </div>
                        <div class="flex-1 mt-sm">
                            <h2>{{ __('advanced-seo::messages.indexing') }}</h2>
                            <p>{{ __('advanced-seo::messages.indexing_description') }}</p>
                        </div>
                    </a>
                </div>
            </div>
        @endcan
        @can('view social defaults')
            <div class="w-full px-2 mb-4 lg:w-1/2 widget">
                <div class="h-full p-0 overflow-hidden card content">
                    <a href="{{ cp_route('advanced-seo.site.edit', 'social') }}" class="flex items-start h-full p-3 hover:bg-blue-100">
                        <div class="w-8 h-8 mr-3 text-blue">
                            @cp_svg('assets')
                        </div>
                        <div class="flex-1 mt-sm">
                            <h2>{{ __('advanced-seo::messages.social') }}</h2>
                            <p>{{ __('advanced-seo::messages.social_description') }}</p>
                        </div>
                    </a>
                </div>
            </div>
        @endcan
        @can('view favicons defaults')
            <div class="w-full px-2 mb-4 lg:w-1/2 widget">
                <div class="h-full p-0 overflow-hidden card content">
                    <a href="{{ cp_route('advanced-seo.site.edit', 'favicons') }}" class="flex items-start h-full p-3 hover:bg-blue-100">
                        <div class="w-8 h-8 mr-3 text-blue">
                            @cp_svg('color')
                        </div>
                        <div class="flex-1 mt-sm">
                            <h2>{{ __('advanced-seo::messages.favicons') }}</h2>
                            <p>{{ __('advanced-seo::messages.favicons_description') }}</p>
                        </div>
                    </a>
                </div>
            </div>
        @endcan
    </div>

    @include('advanced-seo::cp/_docs_callout')

@endsection
