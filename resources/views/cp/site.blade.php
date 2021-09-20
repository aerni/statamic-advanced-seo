@extends('statamic::layout')
@section('title', __('advanced-seo::messages.site'))

@section('content')

    <div class="mb-3">
        <h1>{{ __('advanced-seo::messages.site') }}</h1>
    </div>

    <div class="flex flex-wrap -mx-2 widgets">
        @can('edit seo site defaults')
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
        @can('edit seo general defaults')
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
    </div>

    <div class="-my-4">
        @include('statamic::partials.docs-callout', [
            'topic' => 'Advanced SEO',
            'url' => 'https://statamic.com/addons/aerni/advanced-seo'
        ])
    </div>

@endsection
