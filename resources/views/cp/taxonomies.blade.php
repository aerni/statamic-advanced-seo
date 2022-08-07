@extends('statamic::layout')
@section('title', __('advanced-seo::messages.taxonomies'))

@section('content')

    <div class="mb-3">
        <h1>{{ __('advanced-seo::messages.taxonomies') }}</h1>
    </div>

    <div class="flex flex-wrap -mx-2 widgets">
        <div class="w-full px-2 mb-4 widget">
            <div class="p-0 card content">
                <div class="flex items-start p-4 border-b">
                    <div class="w-8 h-8 mr-2 text-blue">
                        @cp_svg('tags')
                    </div>
                    <div class="flex-1 mt-sm">
                        <h2>{{ __('advanced-seo::messages.taxonomies') }}</h2>
                        <p>{{ __('advanced-seo::messages.taxonomies_description') }}</p>
                    </div>
                </div>
                <div class="p-2">
                    @foreach (Aerni\AdvancedSeo\Models\Defaults::enabledInType('taxonomies') as $taxonomy)
                        @can("view seo {$taxonomy['handle']} defaults")
                            <a href="{{ cp_route('advanced-seo.taxonomies.edit', $taxonomy['handle']) }}" class="block px-1.5 py-1 text-sm rounded-md hover:bg-blue-100">{{ $taxonomy['title'] }}</a>
                        @endcan
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @include('advanced-seo::cp/_docs_callout')

@stop
