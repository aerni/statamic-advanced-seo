@extends('statamic::layout')
@section('title', $variables->title())

@section('content')

    <defaults-publish-form
        publish-container="base"
        :initial-actions="{{ json_encode($actions) }}"
        method="patch"
        defaults-url="{{ $defaultsUrl }}"
        defaults-title="{{ $defaultsTitle }}"
        initial-title="{{ $variables->title() }}"
        initial-handle="{{ $variables->handle() }}"
        initial-reference="{{ $reference }}"
        initial-blueprint-handle="{{ $variables->blueprint()->handle() }}"
        :initial-fieldset="{{ json_encode($blueprint) }}"
        :initial-values="{{ empty($values) ? '{}' : json_encode($values) }}"
        :initial-localized-fields="{{ json_encode($localizedFields) }}"
        :initial-meta="{{ empty($meta) ? '{}' : json_encode($meta) }}"
        :initial-localizations="{{ json_encode($localizations) }}"
        :initial-has-origin="{{ Statamic\Support\Str::bool($hasOrigin) }}"
        :initial-is-root="{{ Statamic\Support\Str::bool($isRoot) }}"
        :initial-origin-values="{{ json_encode($originValues) }}"
        initial-site="{{ $locale }}"
        :can-edit="{{ json_encode($canEdit) }}"
    ></defaults-publish-form>

    @include('advanced-seo::cp/_docs_callout')

@stop
