<script setup>
import { Head } from '@statamic/cms/inertia';
import { Header, Button, Badge, Listing, StatusIndicator } from '@statamic/cms/ui';

defineProps({
    title: String,
    listingUrl: String,
    filters: Array,
});
</script>

<template>
    <Head :title />

    <Header :title icon="alert-warning-exclamation-mark" />

    <Listing
        :url="listingUrl"
        :filters="filters"
        :allow-presets="false"
        preferences-prefix="advanced-seo.redirect-errors"
        sort-column="url"
        sort-direction="asc"
        push-query
    >
        <template #cell-url="{ row: error }">
            <span class="title-index-field">{{ error.url }}</span>
        </template>
        <template #cell-site="{ row: error }">
            {{ error.site_name }}
        </template>
        <template #cell-first_seen_at="{ row: error }">
            <date-time v-if="error.first_seen_at" :of="error.first_seen_at" />
        </template>
        <template #cell-last_seen_at="{ row: error }">
            <date-time v-if="error.last_seen_at" :of="error.last_seen_at" />
        </template>
        <template #cell-redirect="{ row: error }">
            <Button
                v-if="error.status === 'unhandled'"
                size="sm"
                :href="error.create_redirect_url"
                :text="__('advanced-seo::messages.redirect_error_create_redirect')"
            />
            <a
                v-else
                :href="error.redirect_url"
                :title="__(`advanced-seo::messages.redirect_error_status_${error.status}`)"
            >
                <Badge color="default">
                    <span class="flex items-center gap-1.5">
                        <StatusIndicator :status="error.status === 'handled' ? 'published' : 'draft'" class="shrink-0" />
                        <span class="truncate max-w-[20rem]" v-text="error.destination" />
                    </span>
                </Badge>
            </a>
        </template>
    </Listing>
</template>
