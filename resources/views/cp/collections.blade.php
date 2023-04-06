@extends('statamic::layout')
@section('title', __('advanced-seo::messages.collections'))

@section('content')

    <div class="mb-6">
        <h1>{{ __('advanced-seo::messages.collections') }}</h1>
    </div>

    <div class="widgets @container flex flex-wrap -mx-4">
        <div class="w-full px-4 widget md:w-full">
            <div class="p-0 card content">
                <div class="flex items-start px-8 py-8 border-b">
                    <div class="w-8 h-8 mr-4 text-gray-800">
                        @cp_svg('icons/default/content-writing')
                    </div>
                    <div class="flex-1">
                        <h3 class="mb-2 text-blue">{{ __('advanced-seo::messages.collections') }}</h3>
                        <p>{{ __('advanced-seo::messages.collections_description') }}</p>
                    </div>
                </div>
                <div class="px-6 py-6">
                    @foreach (Aerni\AdvancedSeo\Models\Defaults::enabledInType('collections') as $collection)
                        @can("view seo {$collection['handle']} defaults")
                            <a href="{{ cp_route('advanced-seo.collections.edit', $collection['handle']) }}" class="block px-3 py-2 -mx-1 text-sm rounded-md hover:bg-gray-200">{{ $collection['title'] }}</a>
                        @endcan
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @include('advanced-seo::cp/_docs_callout')

@stop
