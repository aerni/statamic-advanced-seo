<script setup>
import { Modal, Description, ErrorMessage, Button, PublishContainer, PublishFieldsProvider, PublishFields } from '@statamic/cms/ui';
import { ref, getCurrentInstance } from 'vue';

const props = defineProps({
    importUrl: String,
});

const instance = getCurrentInstance();
const { $axios } = instance.appContext.config.globalProperties;

const emit = defineEmits(['closed', 'imported']);

const open = ref(true);
const busy = ref(false);
const error = ref(null);
const rowErrors = ref([]);
const values = ref({ file: [] });
const meta = ref({
    file: {
        uploadUrl: cp_url('fieldtypes/files/upload'),
    },
});

const fields = [
    {
        handle: 'file',
        type: 'files',
        display: __('advanced-seo::messages.redirect_import_file'),
        max_files: 1,
        allowed_extensions: ['csv', 'json'],
    },
];

const submit = () => {
    if (! values.value.file.length) return;

    busy.value = true;
    error.value = null;
    rowErrors.value = [];

    $axios.post(props.importUrl, values.value)
        .then((response) => {
            const { imported, errors } = response.data;

            if (errors && errors.length) {
                rowErrors.value = errors;
                return;
            }

            Statamic.$toast.success(__n('advanced-seo::messages.redirect_imported', imported, { count: imported }));
            emit('imported');
            close();
        })
        .catch((e) => {
            if (e.response?.status === 422) {
                const firstError = Object.values(e.response.data.errors ?? {})[0]?.[0];
                error.value = firstError || e.response.data.message;
            } else {
                error.value = __('advanced-seo::messages.redirect_import_something_wrong');
            }
        })
        .finally(() => {
            busy.value = false;
        });
};

const close = () => {
    open.value = false;
    setTimeout(() => emit('closed'), 200);
};
</script>

<template>
    <Modal
        :title="__('advanced-seo::messages.redirect_import_title')"
        :open="open"
        :dismissable="! busy"
        @dismissed="close"
        @update:model-value="close"
    >
        <Description class="mb-6">
            <p>{{ __('advanced-seo::messages.redirect_import_instructions') }}</p>
            <ul class="list-disc list-inside mt-2 font-mono text-xs">
                <li>source ({{ __('advanced-seo::messages.redirect_import_required') }})</li>
                <li>destination ({{ __('advanced-seo::messages.redirect_import_required') }})</li>
                <li>response_code</li>
                <li>preserve_query_string</li>
                <li>enabled</li>
                <li>description</li>
                <li>site ({{ __('advanced-seo::messages.redirect_import_required_multisite') }})</li>
            </ul>
            <p class="mt-3">{{ __('advanced-seo::messages.redirect_import_behavior') }}</p>
            <p class="mt-2">
                <a href="https://advanced-seo.michaelaerni.ch/usage/redirects" target="_blank" rel="noopener noreferrer" class="text-blue-500 hover:underline">
                    {{ __('advanced-seo::messages.redirect_import_docs') }}
                </a>
            </p>
        </Description>

        <PublishContainer
            :blueprint="fields"
            :meta="meta"
            :track-dirty-state="false"
            v-model="values"
        >
            <PublishFieldsProvider :fields="fields">
                <PublishFields />
            </PublishFieldsProvider>
        </PublishContainer>

        <ErrorMessage v-if="error" :text="error" />

        <div v-if="rowErrors.length" class="mt-4">
            <p class="text-sm font-medium text-red-500 mb-2">{{ __n('advanced-seo::messages.redirect_import_failed', rowErrors.length, { count: rowErrors.length }) }}</p>
            <ul class="max-h-40 overflow-y-auto rounded-md border border-gray-200 divide-y divide-gray-100 text-sm">
                <li v-for="rowError in rowErrors" :key="rowError.row" class="px-3 py-2">
                    <div class="flex items-baseline justify-between gap-3">
                        <span class="font-mono text-gray-800 break-all">{{ rowError.source }}</span>
                        <span class="shrink-0 text-xs text-gray-400 tabular-nums">#{{ rowError.row }}</span>
                    </div>
                    <p class="text-red-600 mt-0.5">{{ rowError.message }}</p>
                </li>
            </ul>
        </div>

        <template #footer>
            <div class="flex items-center justify-end space-x-3 pt-3 pb-1">
                <Button variant="ghost" :disabled="busy" :text="__('Cancel')" @click="close" />
                <Button
                    type="submit"
                    variant="primary"
                    :disabled="busy || ! values.file.length"
                    :text="__('advanced-seo::messages.redirect_import')"
                    @click="submit"
                />
            </div>
        </template>
    </Modal>
</template>
