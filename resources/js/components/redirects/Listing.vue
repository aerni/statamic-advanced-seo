<script setup>
import { Header, Button, Badge, Listing, DropdownItem, Icon, Dropdown, DropdownMenu } from '@statamic/cms/ui';
import { useTemplateRef } from 'vue';

defineProps({
    title: String,
    listingUrl: String,
    actionUrl: String,
    filters: Array,
    canImportExport: Boolean,
    createUrl: String,
    exportCsvUrl: String,
    exportJsonUrl: String,
});

defineEmits(['import']);

const listing = useTemplateRef('listing');

defineExpose({
    refresh: () => listing.value?.refresh(),
});
</script>

<template>
    <Header :title="title" icon="moved">
        <Dropdown v-if="canImportExport">
            <template #trigger>
                <Button :text="__('advanced-seo::messages.redirect_import_export')" />
            </template>
            <DropdownMenu>
                <DropdownItem :text="__('advanced-seo::messages.redirect_import')" icon="upload" @click="$emit('import')" />
                <DropdownItem :text="__('advanced-seo::messages.redirect_export_csv')" icon="download" :href="exportCsvUrl" target="_blank" />
                <DropdownItem :text="__('advanced-seo::messages.redirect_export_json')" icon="download" :href="exportJsonUrl" target="_blank" />
            </DropdownMenu>
        </Dropdown>
        <Button :href="createUrl" :text="__('advanced-seo::messages.redirect_create_title')" variant="primary" />
    </Header>

    <Listing
        ref="listing"
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
                <span class="truncate max-w-[20rem]" v-text="redirect.destination" />
            </a>
            <span v-else class="truncate max-w-[20rem] block" v-text="redirect.destination" />
        </template>
        <template #cell-response_code="{ row: redirect }">
            {{ redirect.response_code_label }}
        </template>
        <template #cell-preserve_query_string="{ row: redirect }">
            <Icon v-if="redirect.preserve_query_string" name="checkmark" class="size-4" />
        </template>
        <template #cell-origin="{ row: redirect }">
            {{ redirect.origin_label }}
        </template>
        <template #cell-site="{ row: redirect }">
            {{ redirect.site_name }}
        </template>
        <template #cell-last_hit_at="{ row: redirect }">
            <date-time v-if="redirect.last_hit_at" :of="redirect.last_hit_at" :options="{ relative: true }" />
        </template>
        <template #cell-created_at="{ row: redirect }">
            <date-time v-if="redirect.created_at" :of="redirect.created_at" />
        </template>
        <template #cell-status="{ row: redirect }">
            <Badge v-if="redirect.status" color="green" :text="__('advanced-seo::messages.enabled')" pill />
            <Badge v-else color="default" :text="__('Disabled')" pill />
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
