@extends('statamic::layout')
@section('title', __('advanced-seo::messages.collections'))

@section('content')

    <div class="mb-3">
        <h1>{{ __('advanced-seo::messages.collections') }}</h1>
    </div>

    <div class="flex flex-wrap -mx-2 widgets">
        <div class="w-full px-2 mb-4 widget">
            <div class="p-0 card content">
                <div class="flex items-start p-4 border-b">
                    <div class="w-8 h-8 mr-2 text-blue">
                        @cp_svg('icons/default/content-writing')
                    </div>
                    <div class="flex-1 mt-sm">
                        <h2>{{ __('advanced-seo::messages.collections') }}</h2>
                        <p>{{ __('advanced-seo::messages.collections_description') }}</p>
                    </div>
                </div>
                <div class="p-2">
                    @foreach (Aerni\AdvancedSeo\Models\Defaults::enabledInType('collections') as $collection)
                        @can("view seo {$collection['handle']} defaults")
                            <a href="{{ cp_route('advanced-seo.collections.edit', $collection['handle']) }}" class="block px-1.5 py-1 text-sm rounded-md hover:bg-blue-100">{{ $collection['title'] }}</a>
                        @endcan
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @include('advanced-seo::cp/_docs_callout')

@stop
