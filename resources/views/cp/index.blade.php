@extends('statamic::layout')
@section('title', __('advanced-seo::messages.dashboard_title'))

@section('content')

    <div class="mb-3">
        <h1>{{ __('advanced-seo::messages.dashboard_title') }}</h1>
    </div>

    <div class="flex flex-wrap -mx-2 widgets">
        @can('siteDefaultsIndex', Aerni\AdvancedSeo\Data\SeoVariables::class)
            <div class="w-full px-2 mb-4 lg:w-1/2 widget">
                <div class="h-full p-0 overflow-hidden card content">
                    <a href="{{ cp_route('advanced-seo.show', 'site') }}" class="flex items-start h-full p-3 hover:bg-blue-100">
                        <div class="w-8 h-8 mr-3 text-blue">
                            @cp_svg('sites')
                        </div>
                        <div class="flex-1 mt-sm">
                            <h2>{{ __('advanced-seo::messages.site') }}</h2>
                            <p>{{ __('advanced-seo::messages.site_description') }}</p>
                        </div>
                    </a>
                </div>
            </div>
        @endcan
        @can('contentDefaultsIndex', Aerni\AdvancedSeo\Data\SeoVariables::class)
            <div class="w-full px-2 mb-4 lg:w-1/2 widget">
                <div class="h-full p-0 overflow-hidden card content">
                    <a href="{{ cp_route('advanced-seo.show', 'content') }}" class="flex items-start h-full p-3 hover:bg-blue-100">
                        <div class="w-8 h-8 mr-3 text-blue">
                            @cp_svg('content-writing')
                        </div>
                        <div class="flex-1 mt-sm">
                            <h2>{{ __('advanced-seo::messages.content') }}</h2>
                            <p>{{ __('advanced-seo::messages.content_description') }}</p>
                        </div>
                    </a>
                </div>
            </div>
        @endcan
    </div>

    {{-- <div class="flex flex-wrap -mx-2 widgets">
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
    </div> --}}

    {{-- <div class="flex flex-wrap -mx-2 widgets">
        <div class="w-full px-2 mb-4 widget">
            <div class="p-0 card content">
                <div class="flex flex-wrap p-2">
                    @can('edit seo site defaults')
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
                    @can('edit seo content defaults')
                        <a href="{{ cp_route('advanced-seo.site.edit', 'marketing') }}" class="flex items-start w-full p-2 rounded-md lg:w-1/2 hover:bg-blue-100 group">
                            <div class="w-8 h-8 mr-2 text-blue">
                                @cp_svg('charts')
                            </div>
                            <div class="flex-1 mt-sm">
                                <h2>{{ __('advanced-seo::messages.marketing') }}</h2>
                                <p>{{ __('advanced-seo::messages.marketing_description') }}</p>
                            </div>
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div> --}}

    {{-- <div class="mb-3">
        <h1>{{ __('advanced-seo::messages.content') }}</h1>
    </div> --}}

    {{-- <div class="flex flex-wrap -mx-2 widgets">
        @if (Statamic\Facades\Collection::all()->count() > 0)
            <div class="w-full px-2 mb-4 lg:w-1/2 widget">
                <div class="p-0 card content">
                    <div class="flex items-start p-3 border-b">
                        <div class="w-8 h-8 mr-3 text-blue">
                            @cp_svg('content-writing')
                        </div>
                        <div class="flex-1 mt-sm">
                            <h2>{{ __('Collections') }}</h2>
                            <p>{{ __('advanced-seo::messages.general_description') }}</p>
                        </div>
                    </div>
                    <div class="p-1.5">
                        @foreach (Statamic\Facades\Collection::all() as $collection)
                            <a href="{{ cp_route('advanced-seo.content.collections.edit', $collection) }}" class="block px-1.5 py-1 text-sm rounded-md hover:bg-blue-100">{{ $collection->title() }}</a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if (Statamic\Facades\Taxonomy::all()->count() > 0)
            <div class="w-full px-2 mb-4 lg:w-1/2 widget">
                <div class="p-0 card content">
                    <div class="flex items-start p-3 border-b">
                        <div class="w-8 h-8 mr-3 text-blue">
                            @cp_svg('tags')
                        </div>
                        <div class="flex-1 mt-sm">
                            <h2>{{ __('Taxonomies') }}</h2>
                            <p>{{ __('advanced-seo::messages.general_description') }}</p>
                        </div>
                    </div>
                    <div class="p-1.5">
                        @foreach (Statamic\Facades\Taxonomy::all() as $taxonomy)
                            <a href="{{ cp_route('advanced-seo.content.taxonomies.edit', $taxonomy) }}" class="block px-1.5 py-1 text-sm rounded-md hover:bg-blue-100">{{ $taxonomy->title() }}</a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div> --}}

    @include('advanced-seo::cp/_docs_callout')

@endsection
