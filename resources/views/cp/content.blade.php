@extends('statamic::layout')
@section('title', __('advanced-seo::messages.content'))

@section('content')

    <div class="flex items-center mb-3">
        <h1 class="flex-1">{{ __('advanced-seo::messages.content') }}</h1>
    </div>

    <h3 class="pl-0 mb-1 little-heading">{{ __('Collections') }}</h3>
    <div class="p-0 mb-2 card">
        <table class="data-table">
            @foreach (Statamic\Facades\Collection::all() as $collection)
                    <tr>
                        <td>
                            <div class="flex items-center">
                                <div class="w-4 h-4 mr-2">@cp_svg('content-writing')</div>
                                <a href="{{ cp_route('advanced-seo.content.collections.edit', $collection) }}">{{ $collection->title() }}</a>
                            </div>
                        </td>
                    </tr>
            @endforeach
        </table>
    </div>

    <h3 class="pl-0 mb-1 little-heading">{{ __('Taxonomies') }}</h3>
    <div class="p-0 mb-2 card">
        <table class="data-table">
            @foreach (Statamic\Facades\Taxonomy::all() as $taxonomy)
                <tr>
                    <td>
                        <div class="flex items-center">
                            <div class="w-4 h-4 mr-2">@cp_svg('tags')</div>
                            <a href="{{ cp_route('advanced-seo.content.taxonomies.edit', $taxonomy) }}">{{ $taxonomy->title() }}</a>
                        </div>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>

    @include('statamic::partials.docs-callout', [
        'topic' => 'Advanced SEO',
        'url' => 'https://statamic.com/addons/aerni/advanced-seo'
    ])

@stop
