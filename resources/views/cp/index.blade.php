@extends('statamic::layout')
@section('title', __('advanced-seo::messages.dashboard_title'))

@section('content')

    <div class="mb-3">
        <h1>{{ __('advanced-seo::messages.dashboard_title') }}</h1>
    </div>

    <div class="flex flex-wrap -mx-2 widgets">
        <div class="w-full px-2 mb-4 widget">
            <div class="p-0 card content">
                <div class="flex flex-wrap p-2">
                    @can('siteDefaultsIndex', Aerni\AdvancedSeo\Data\SeoVariables::class)
                        <a href="{{ cp_route('advanced-seo.show', 'site') }}" class="flex items-start w-full p-2 rounded-md lg:w-1/2 hover:bg-blue-100 group">
                            <div class="w-8 h-8 mr-2 text-blue">
                                @cp_svg('earth')
                            </div>
                            <div class="flex-1 mt-sm">
                                <h2>{{ __('advanced-seo::messages.site') }}</h2>
                                <p>{{ __('advanced-seo::messages.site_description') }}</p>
                            </div>
                        </a>
                    @endcan
                    @can('contentDefaultsIndex', Aerni\AdvancedSeo\Data\SeoVariables::class)
                        <a href="{{ cp_route('advanced-seo.show', 'content') }}" class="flex items-start w-full p-2 rounded-md lg:w-1/2 hover:bg-blue-100 group">
                            <div class="w-8 h-8 mr-2 text-blue">
                                @cp_svg('content-writing')
                            </div>
                            <div class="flex-1 mt-sm">
                                <h2>{{ __('advanced-seo::messages.content') }}</h2>
                                <p>{{ __('advanced-seo::messages.content_description') }}</p>
                            </div>
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    @include('advanced-seo::cp/_docs_callout')

@endsection
