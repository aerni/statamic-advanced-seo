<script setup>
import { Head } from '@statamic/cms/inertia';
import { Header, Button, Badge, Listing, DropdownItem, Icon, Panel, Card, EmptyStateItem } from '@statamic/cms/ui';

defineProps({
    title: String,
    createUrl: String,
    listingUrl: String,
    actionUrl: String,
    canCreate: Boolean,
    filters: Array,
    hasRedirects: Boolean,
});
</script>

<template>
    <Head :title />

    <template v-if="hasRedirects">
        <Header :title icon="moved">
            <Button v-if="canCreate" :href="createUrl" :text="__('advanced-seo::messages.redirect_create_title')" variant="primary" />
        </Header>

        <Listing
            :url="listingUrl"
            :action-url="actionUrl"
            :allow-presets="false"
            preferences-prefix="advanced-seo.redirects"
            sort-column="source"
            sort-direction="asc"
            :filters="filters"
            push-query
        >
            <template #cell-source="{ row: redirect }">
                <a v-if="redirect.editable" :href="redirect.edit_url" class="title-index-field">
                    {{ redirect.source }}
                </a>
                <span v-else class="title-index-field">{{ redirect.source }}</span>
            </template>
            <template #cell-destination="{ row: redirect }">
                <a v-if="redirect.destination_url" :href="redirect.destination_url" target="_blank" rel="noopener noreferrer" class="flex items-center gap-1.5">
                    <Icon v-if="redirect.destination_is_entry" name="collections" class="shrink-0 size-4" />
                    <Icon v-else name="external-link" class="shrink-0 size-4" />
                    <span v-text="redirect.destination" />
                </a>
                <span v-else v-text="redirect.destination" />
            </template>
            <template #cell-response_code="{ row: redirect }">
                {{ redirect.response_code_label }}
            </template>
            <template #cell-forward_query_string="{ row: redirect }">
                <Icon v-if="redirect.forward_query_string" name="checkmark" class="size-4" />
            </template>
            <template #cell-automatic="{ row: redirect }">
                {{ redirect.automatic
                    ? __('advanced-seo::messages.redirect_automatic')
                    : __('advanced-seo::messages.redirect_manual') }}
            </template>
            <template #cell-site="{ row: redirect }">
                {{ redirect.site_name }}
            </template>
            <template #cell-status="{ row: redirect }">
                <Badge
                    v-if="redirect.status"
                    color="green"
                    :text="__('advanced-seo::messages.enabled')"
                    pill
                />
                <Badge
                    v-else
                    color="default"
                    :text="__('Disabled')"
                    pill
                />
            </template>
            <template #prepended-row-actions="{ row: redirect }">
                <DropdownItem
                    v-if="redirect.test_url && redirect.status"
                    :text="__('advanced-seo::messages.test_redirect')"
                    icon="external-link"
                    :href="redirect.test_url"
                    target="_blank"
                    rel="noopener noreferrer"
                />
                <DropdownItem v-if="redirect.editable" :text="__('Edit')" icon="edit" :href="redirect.edit_url" />
            </template>
        </Listing>
    </template>

    <template v-else>
        <header class="py-8 pt-16 text-center">
            <h1 class="text-[25px] font-medium antialiased flex justify-center items-center gap-2 sm:gap-3">
                <Icon name="moved" class="text-gray-500 size-5.5" />
                {{ __('advanced-seo::messages.redirects') }}
            </h1>
        </header>

        <Panel class="max-w-md mx-auto">
            <Card>
                <ul class="flex flex-wrap [:has(>&)]:p-1.5">
                    <EmptyStateItem
                        v-if="canCreate"
                        :href="createUrl"
                        icon="moved"
                        :heading="__('advanced-seo::messages.redirect_create_title')"
                        :description="__('advanced-seo::messages.redirect_create_description')"
                    />
                </ul>
            </Card>
        </Panel>
    </template>
</template>
