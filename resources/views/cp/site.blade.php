@extends('statamic::layout')
@section('title', __('advanced-seo::messages.site'))

@section('content')

    <div class="mb-6">
        <h1>{{ __('advanced-seo::messages.site') }}</h1>
    </div>

    <div class="widgets @container flex flex-wrap -mx-4 py-2">
        <div class="w-full px-4 mb-8 widget md:w-full">
            <div class="p-0 card content">
                <div class="flex flex-wrap p-4">
                    @foreach (Aerni\AdvancedSeo\Models\Defaults::enabledInType('site') as $site)
                        @can("view seo {$site['handle']} defaults")
                            <a href="{{ cp_route('advanced-seo.site.edit', $site['handle']) }}" class="flex items-start w-full p-4 rounded-md lg:w-1/2 hover:bg-gray-200 group">
                                <div class="w-8 h-8 mr-4 text-gray-800">
                                    @cp_svg($site['icon'])
                                </div>
                                <div class="flex-1">
                                    <h3 class="mb-2 text-blue">{{ __("advanced-seo::messages.{$site['handle']}") }}</h3>
                                    <p>{{ __("advanced-seo::messages.{$site['handle']}_description") }}</p>
                                </div>
                            </a>
                        @endcan
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @include('advanced-seo::cp/_docs_callout')

@endsection
