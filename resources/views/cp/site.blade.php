@extends('statamic::layout')
@section('title', __('advanced-seo::messages.site'))

@section('content')

    <div class="mb-3">
        <h1>{{ __('advanced-seo::messages.site') }}</h1>
    </div>

    <div class="flex flex-wrap -mx-2 widgets">
        <div class="w-full px-2 mb-4 widget">
            <div class="p-0 card content">
                <div class="flex flex-wrap p-2">
                    @foreach (Aerni\AdvancedSeo\Models\Defaults::enabledInType('site') as $site)
                        @can("view seo {$site['handle']} defaults")
                            <a href="{{ cp_route('advanced-seo.site.edit', $site['handle']) }}" class="flex items-start w-full p-2 rounded-md lg:w-1/2 hover:bg-blue-100 group">
                                <div class="w-8 h-8 mr-2 text-blue">
                                    @cp_svg($site['icon'])
                                </div>
                                <div class="flex-1 mt-sm">
                                    <h2>{{ __("advanced-seo::messages.{$site['handle']}") }}</h2>
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
