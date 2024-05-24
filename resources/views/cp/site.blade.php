@extends('statamic::layout')
@section('title', __('advanced-seo::messages.site'))

@section('content')

    <div class="mb-6">
        <h1>{{ __('advanced-seo::messages.site') }}</h1>
    </div>

    <div class="widgets @container flex flex-wrap -mx-4">
        <div class="w-full px-4 widget md:w-full">
            <div class="p-0 card card-lg content">
                <div class="flex flex-wrap p-4">
                    @foreach ($defaults as $default)
                        <a href="{{ cp_route('advanced-seo.site.edit', $default['handle']) }}" class="w-full p-4 border border-transparent rounded-md md:items-start md:flex lg:w-1/2 hover:bg-gray-200 dark:hover:bg-dark-575 dark:hover:border-dark-400 group">
                            <div class="w-8 h-8 mr-4 text-gray-800 dark:text-dark-175">
                                @cp_svg($default['icon'])
                            </div>
                            <div class="flex-1 text-blue">
                                <h3>{{ __("advanced-seo::messages.{$default['handle']}") }}</h3>
                                <p>{{ __("advanced-seo::messages.{$default['handle']}_description") }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @include('advanced-seo::cp/_docs_callout')

@endsection
