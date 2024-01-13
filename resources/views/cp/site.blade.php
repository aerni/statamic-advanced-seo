@extends('statamic::layout')
@section('title', __('advanced-seo::messages.site'))

@section('content')

    <div class="mb-6">
        <h1>{{ __('advanced-seo::messages.site') }}</h1>
    </div>

    <div class="widgets @container flex flex-wrap -mx-4">
        <div class="w-full px-4 widget md:w-full">
            <div class="p-0 card content">
                <div class="flex flex-wrap p-4">
                    @foreach ($defaults as $default)
                        <a href="{{ cp_route('advanced-seo.site.edit', $default['handle']) }}" class="flex items-start w-full p-4 rounded-md lg:w-1/2 hover:seo-bg-blue-100 group">
                            <div class="w-8 h-8 mr-4 text-blue">
                                @cp_svg($default['icon'])
                            </div>
                            <div class="flex-1">
                                <h3 class="mb-2 text-lg text-gray-800 group-hover:text-blue">{{ __("advanced-seo::messages.{$default['handle']}") }}</h3>
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
