<script setup>
import { Head } from '@statamic/cms/inertia';
import { Header, Button, Badge, Listing, Icon, Panel, Card, StatusIndicator } from '@statamic/cms/ui';

defineProps({
    title: String,
    listingUrl: String,
    hasErrors: Boolean,
});
</script>

<template>
    <Head :title />

    <Header :title icon="alert-warning-exclamation-mark" />

    <template v-if="hasErrors">
        <Listing
            :url="listingUrl"
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
                            <StatusIndicator :status="error.status === 'handled' ? 'published' : 'draft'" />
                            <span v-text="error.destination" />
                        </span>
                    </Badge>
                </a>
            </template>
        </Listing>
    </template>

    <template v-else>
        <Panel class="max-w-md mx-auto">
            <Card>
                <div class="p-4 text-center text-gray-500">
                    <Icon name="alert-warning-exclamation-mark" class="size-5.5 mx-auto mb-2" />
                    {{ __('advanced-seo::messages.redirect_errors_empty') }}
                </div>
            </Card>
        </Panel>
    </template>
</template>
