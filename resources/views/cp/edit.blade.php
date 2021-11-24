@inject('str', 'Statamic\Support\Str')
@extends('statamic::layout')
@section('title', $breadcrumbs->title($title))
@section('wrapper_class', 'max-w-3xl')

@section('content')

    <defaults-publish-form
        publish-container="base"
        :initial-actions="{{ json_encode($actions) }}"
        method="patch"
        initial-title="{{ $title }}"
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
        :initial-read-only="{{ $str::bool($readOnly) }}"
        initial-site="{{ $locale }}"
        :breadcrumbs="{{ $breadcrumbs->toJson() }}"
        content-type="{{ $contentType }}"
    ></defaults-publish-form>

@stop
