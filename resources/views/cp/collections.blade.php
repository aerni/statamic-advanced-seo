@extends('statamic::layout')
@section('title', __('advanced-seo::messages.collections'))

@section('content')

    <div class="mb-6">
        <h1>{{ __('advanced-seo::messages.collections') }}</h1>
    </div>

    <div class="widgets @container flex flex-wrap -mx-4">
        <div class="w-full px-4 widget md:w-full">
            <div class="p-0 card card-lg content">
                <header class="flex items-start px-8 py-8 border-b">
                    <div class="w-8 h-8 mr-4 text-gray-800 dark:text-dark-175">
                        @cp_svg('icons/light/content-writing')
                    </div>
                    <div class="flex-1 text-blue">
                        <h3>{{ __('advanced-seo::messages.collections') }}</h3>
                        <p>{{ __('advanced-seo::messages.collections_description') }}</p>
                    </div>
                </header>
                <div class="px-6 py-6">
                    @foreach ($defaults as $default)
                        <a href="{{ cp_route('advanced-seo.collections.edit', $default['handle']) }}" class="block px-3 py-2 -mx-1 text-sm border border-transparent rounded-md hover:bg-gray-200 dark:hover:bg-dark-575 dark:hover:border-dark-400">{{ $default['title'] }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @include('advanced-seo::cp/_docs_callout')

@stop
