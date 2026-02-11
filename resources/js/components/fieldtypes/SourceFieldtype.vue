<script setup>
import { ref, computed, watch } from 'vue';
import { Fieldtype } from '@statamic/cms';
import { Button, ButtonGroup, Description, injectPublishContext } from '@statamic/cms/ui';
import { useSeoValues } from '../../composables/useSeoValues.js';

const SOURCE_TYPES = {
    AUTO: 'auto',
    DEFAULT: 'default',
    CUSTOM: 'custom',
};

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const fieldtype = Fieldtype.use(emit, props);
defineExpose(fieldtype.expose);

const publishContainer = injectPublishContext();

const seo = useSeoValues();
const customValue = ref(null);

watch(() => props.meta.customValue, (newValue) => {
    customValue.value = newValue;
}, { immediate: true });

watch(() => props.value, (newValue) => {
    if (newValue.source === SOURCE_TYPES.CUSTOM) {
        customValue.value = newValue.value;
    }
}, { deep: true });

const fieldSource = computed(() => props.value.source);
const isCustomSource = computed(() => fieldSource.value === SOURCE_TYPES.CUSTOM);

const autoFieldHandle = computed(() => props.config.auto);
const autoFieldValue = computed(() => autoFieldHandle.value ? seo.resolve(autoFieldHandle.value) : null);
const defaultFieldValue = computed(() => props.meta.default);

const fieldValue = computed(() => valueForSource(fieldSource.value));

const fieldConfig = computed(() => props.config.field);
const fieldMeta = computed(() => props.meta.meta);

const autoFieldDisplay = computed(() => {
    const sections = publishContainer.blueprint.value.tabs.flatMap(tab => tab.sections);
    const fields = sections.flatMap(section => section.fields);
    return fields.find(field => field.handle === autoFieldHandle.value)?.display;
});

const fieldSources = computed(() => {
    const sources = [
        {
            value: SOURCE_TYPES.DEFAULT,
            label: __('advanced-seo::messages.field_sources.default'),
            description: __('advanced-seo::messages.field_source_description.defaults', {
                title: props.meta.title,
            }),
        },
        {
            value: SOURCE_TYPES.CUSTOM,
            label: __('advanced-seo::messages.field_sources.custom'),
            description: '',
        },
    ];

    if (autoFieldHandle.value) {
        sources.unshift({
            value: SOURCE_TYPES.AUTO,
            label: __('advanced-seo::messages.field_sources.auto'),
            description: __('advanced-seo::messages.field_source_description.auto', {
                title: autoFieldDisplay.value,
                handle: autoFieldHandle.value,
            }),
        });
    }

    if (props.config.options) {
        return sources.filter(source => props.config.options.includes(source.value));
    }

    return sources;
});

const sourceDescription = computed(() => {
    return fieldSources.value.find(source => source.value === fieldSource.value).description;
});

function valueForSource(source) {
    return {
        [SOURCE_TYPES.AUTO]: autoFieldValue.value,
        [SOURCE_TYPES.DEFAULT]: defaultFieldValue.value,
        [SOURCE_TYPES.CUSTOM]: customValue.value,
    }[source];
}

function updateFieldSource(source) {
    if (fieldSource.value === source) return;
    fieldtype.update({ source, value: valueForSource(source) });
}

function updateFieldValue(newValue) {
    fieldtype.update({ source: fieldSource.value, value: newValue });
}

function updateFieldMeta(newMeta) {
    fieldtype.updateMeta({ ...props.meta, meta: newMeta || fieldMeta.value });
}
</script>

<template>
    <div class="mt-4 space-y-2">

        <div>
            <ButtonGroup>
                <Button
                    v-for="(source) in fieldSources"
                    :key="source.value"
                    :text="source.label"
                    size="xs"
                    :variant="fieldSource === source.value ? 'pressed' : 'default'"
                    :disabled="props.isReadOnly"
                    @click="updateFieldSource(source.value)"
                />
            </ButtonGroup>
        </div>

        <div>
            <Component
                :is="props.meta.component"
                :name="props.name"
                :config="fieldConfig"
                :meta="fieldMeta"
                :value="fieldValue"
                :read-only="props.isReadOnly || !isCustomSource"
                handle="source_value"
                @update:meta="updateFieldMeta"
                @update:value="updateFieldValue"
            />
        </div>

        <div v-if="!isCustomSource">
            <Description :text="sourceDescription" />
        </div>

    </div>
</template>
