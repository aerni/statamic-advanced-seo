@extends('statamic::layout')
@section('title', __('advanced-seo::messages.getting_started_title'))

@section('content')

    <div class="p-0 mt-1 card content">
        <div class="px-4 py-3 border-b">
            <h1>{{ __('advanced-seo::messages.getting_started_title') }}</h1>
            <p>{{ __('advanced-seo::messages.getting_started_intro') }}</p>
        </div>
        <div class="flex flex-wrap p-2">
            @can('edit seo site defaults')
                <a href="{{ cp_route('advanced-seo.site.general.edit') }}" class="flex items-start w-full p-2 rounded-md lg:w-1/2 hover:bg-grey-20 group">
                    <div class="w-8 h-8 mr-2 text-grey-80">
                        @cp_svg('earth')
                    </div>
                    <div class="flex-1">
                        <h3 class="mb-1 text-blue">{{ __('advanced-seo::messages.site') }}</h3>
                        <p>{{ __('advanced-seo::messages.site_description') }}</p>
                    </div>
                </a>
            @endcan
            @can('edit seo content defaults')
                <a href="{{ cp_route('advanced-seo.content.index') }}" class="flex items-start w-full p-2 rounded-md lg:w-1/2 hover:bg-grey-20 group">
                    <div class="w-8 h-8 mr-2 text-grey-80">
                        @cp_svg('content-writing')
                    </div>
                    <div class="flex-1">
                        <h3 class="mb-1 text-blue">{{ __('advanced-seo::messages.content') }}</h3>
                        <p>{{ __('advanced-seo::messages.content_description') }}</p>
                    </div>
                </a>
            @endcan
        </div>
    </div>

    @include('statamic::partials.docs-callout', [
        'topic' => 'Advanced SEO',
        'url' => 'https://statamic.com/addons/aerni/advanced-seo'
    ])

@endsection
