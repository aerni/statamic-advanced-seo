<script setup>
import { ref, computed, useTemplateRef, getCurrentInstance } from 'vue';
import { Head } from '@statamic/cms/inertia';
import { Header, Button, Badge, Listing, StatusIndicator, Stack, PublishContainer, PublishTabs, Panel, Heading, Switch, ConfirmationModal } from '@statamic/cms/ui';
import { Pipeline, Request } from '@statamic/cms/save-pipeline';

const props = defineProps({
    title: String,
    listingUrl: String,
    actionUrl: String,
    filters: Array,
    canCreate: { type: Boolean, default: false },
    createUrl: { type: String, default: null },
    createBlueprint: { type: Object, default: null },
    createValues: { type: Object, default: null },
    createMeta: { type: Object, default: null },
    canClear: { type: Boolean, default: false },
    clearUrl: { type: String, default: null },
    hasErrors: { type: Boolean, default: false },
});

const listing = useTemplateRef('listing');
const container = useTemplateRef('container');

const creating = ref(null);
const values = ref({});
const meta = ref(props.createMeta);
const enabled = ref(true);
const errors = ref({});
const saving = ref(false);

const isCreating = computed(() => creating.value !== null);

const confirmingClear = ref(false);
const clearing = ref(false);
const anyErrors = ref(props.hasErrors);

const { $axios } = getCurrentInstance().appContext.config.globalProperties;

function clearAll() {
    clearing.value = true;

    $axios.post(props.clearUrl)
        .then(() => {
            Statamic.$toast.success(__('advanced-seo::messages.redirect_errors_cleared'));
            confirmingClear.value = false;
            anyErrors.value = false;
            listing.value.refresh();
        })
        .finally(() => (clearing.value = false));
}

function openCreate(error) {
    values.value = { ...props.createValues, source: error.url, site: error.site };
    enabled.value = true;
    errors.value = {};
    creating.value = { source: error.url, site: error.site };
}

function save() {
    new Pipeline()
        .provide({ container, errors, saving })
        .through([
            new Request(props.createUrl, 'post', { enabled: enabled.value, origin: 'error' }),
        ])
        .then(() => {
            Statamic.$toast.success(__('Saved'));
            creating.value = null;
            listing.value.refresh();
        });
}
</script>

<template>
    <Head :title />

    <Header :title icon="alert-warning-exclamation-mark">
        <Button
            v-if="canClear && anyErrors"
            variant="primary"
            :text="__('advanced-seo::messages.redirect_errors_clear')"
            @click="confirmingClear = true"
        />
    </Header>

    <ConfirmationModal
        v-if="confirmingClear"
        :open="confirmingClear"
        :title="__('advanced-seo::messages.redirect_errors_clear')"
        :body-text="__('advanced-seo::messages.redirect_errors_clear_confirmation')"
        :button-text="__('advanced-seo::messages.redirect_errors_clear')"
        danger
        :busy="clearing"
        @confirm="clearAll"
        @update:open="(open) => { if (! open) confirmingClear = false; }"
    />

    <Listing
        ref="listing"
        :url="listingUrl"
        :action-url="actionUrl"
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
            <date-time v-if="error.last_seen_at" :of="error.last_seen_at" :options="{ relative: true }" />
        </template>
        <template #cell-redirect="{ row: error }">
            <Button
                v-if="error.status === 'unhandled' && canCreate"
                size="sm"
                icon="plus"
                :text="__('advanced-seo::messages.redirect_error_create_redirect')"
                @click="openCreate(error)"
            />
            <a
                v-else-if="error.status !== 'unhandled'"
                :href="error.redirect_url"
                :title="__(`advanced-seo::messages.redirect_error_status_${error.status}`)"
            >
                <Badge color="default">
                    <span class="flex items-center gap-1.5">
                        <StatusIndicator :status="error.status === 'handled' ? 'published' : 'draft'" class="shrink-0" />
                        <span class="truncate max-w-[20rem]" v-text="error.destination || error.response_code_label" />
                    </span>
                </Badge>
            </a>
        </template>
    </Listing>

    <Stack
        v-if="createBlueprint"
        size="half"
        :open="isCreating"
        :title="__('advanced-seo::messages.redirect_error_create_redirect')"
        icon="moved"
        @update:open="(open) => { if (! open) creating = null; }"
    >
        <PublishContainer
            ref="container"
            :key="creating?.source"
            name="redirect-create"
            :blueprint="createBlueprint"
            :meta="meta"
            :errors="errors"
            v-model="values"
        >
            <PublishTabs>
                <template #actions>
                    <Panel class="flex justify-between px-5! py-3! dark:bg-gray-800!">
                        <Heading :text="__('advanced-seo::fields.redirect_enabled.display')" />
                        <Switch v-model="enabled" />
                    </Panel>
                </template>
            </PublishTabs>
        </PublishContainer>

        <div class="mt-4 flex justify-end">
            <Button variant="primary" :text="__('Save')" :disabled="saving" @click="save" />
        </div>
    </Stack>
</template>
