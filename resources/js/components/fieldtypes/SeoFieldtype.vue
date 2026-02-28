<script setup>
import { computed, ref, watch } from 'vue';
import { Fieldtype } from '@statamic/cms';
import { Description, injectPublishContext } from '@statamic/cms/ui';
const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const fieldtype = Fieldtype.use(emit, props);
defineExpose(fieldtype.expose);

const publishContext = injectPublishContext();

// Snapshots of the local cascade defaults. During sync, Statamic swaps meta to the origin's
// context (Container.vue syncField) — we detect this and correct it back. Navigation (locale
// switch) is distinguished by a site change: sync keeps the same site, navigation changes it.
let lastSite = publishContext?.site?.value;

const localDefaultValue = ref(props.meta.defaultValue);
const localDefaultMeta = ref(props.meta.defaultMeta);
const originDefaultValue = ref(props.meta.originDefaultValue);

watch(() => props.meta, (newMeta) => {
    const currentSite = publishContext?.site?.value;

    if (currentSite !== lastSite) {
        // Site changed — navigation. Accept the new locale's values.
        lastSite = currentSite;
        localDefaultValue.value = newMeta.defaultValue;
        localDefaultMeta.value = newMeta.defaultMeta;
        originDefaultValue.value = newMeta.originDefaultValue;
        return;
    }

    // Same site. If the default changed, it's a sync swap — correct back to local defaults.
    if (JSON.stringify(newMeta.defaultValue) !== JSON.stringify(localDefaultValue.value)) {
        fieldtype.updateMeta({
            ...newMeta,
            defaultValue: localDefaultValue.value,
            defaultMeta: localDefaultMeta.value,
            originDefaultValue: originDefaultValue.value,
        });
    }
});

const isCustom = computed(() => props.value.source === 'custom');
const isTextBasedField = computed(() => props.meta.isTextBasedField);
const isCodeField = computed(() => props.meta.component === 'code-fieldtype');


// Non-text field synced to origin where the origin's cascade resolves differently.
// After sync, props.value.value holds the origin's preprocessed default (e.g. false).
// After reset, props.value.value matches localDefaultValue (e.g. true).
const showsOriginDefault = computed(() => {
    return !isCustom.value
        && !isTextBasedField.value
        && originDefaultValue.value != null
        && JSON.stringify(originDefaultValue.value) !== JSON.stringify(localDefaultValue.value)
        && JSON.stringify(props.value.value) !== JSON.stringify(localDefaultValue.value);
});

const childValue = computed(() => {
    if (isCustom.value) {
        return props.value.value;
    }

    // Non-custom text/code fields: show the default value.
    if (isTextBasedField.value || isCodeField.value) {
        return localDefaultValue.value;
    }

    return showsOriginDefault.value ? originDefaultValue.value : localDefaultValue.value;
});

const childMeta = computed(() => props.meta.meta);

const showResetButton = computed(() => !fieldtype.isReadOnly.value && (isCustom.value || showsOriginDefault.value));

function reset() {
    fieldtype.update({ source: 'default', value: localDefaultValue.value });
    fieldtype.updateMeta({ ...props.meta, meta: localDefaultMeta.value });
}

// Code and asset fields may re-emit their value when receiving new props
// (e.g. after reset). Ignore emissions that match the current default.
function matchesDefault(value) {
    if (isCustom.value) return false;

    // CodeMirror normalizes null to '' — compare code content directly.
    if (isCodeField.value) return (value.code ?? '') === (localDefaultValue.value?.code ?? '');

    // Assets, selects, toggles — deep compare.
    if (!isTextBasedField.value) return JSON.stringify(value) === JSON.stringify(localDefaultValue.value);

    return false;
}

function updateFieldValue(value) {
    if (matchesDefault(value)) return;

    fieldtype.update({ source: 'custom', value });
}

function updateFieldMeta(meta) {
    fieldtype.updateMeta({ ...props.meta, meta: meta || childMeta.value });
}

function handleBlur() {
    if (!isCustom.value || !isTextBasedField.value) return;

    // Code fieldtype stores { code: '...', mode: '...' }.
    if (isCodeField.value) {
        const value = props.value.value.code.trim();
        if (!value) return reset();
        return updateFieldValue({ ...props.value.value, code: value });
    }

    const value = String(props.value.value ?? '').trim();
    if (!value) return reset();
    updateFieldValue(value);
}
</script>

<template>
    <div :class="{ 'opacity-50': isCodeField && !isCustom }" @focusout="handleBlur">
        <Component
            :is="props.meta.component"
            :handle="`${props.config.handle}_child`"
            :name="props.name"
            :config="props.config.field"
            :meta="childMeta"
            :value="childValue"
            :read-only="fieldtype.isReadOnly.value"
            @update:meta="updateFieldMeta"
            @update:value="updateFieldValue"
        />
    </div>

    <button
        v-if="showResetButton"
        type="button"
        class="mt-2 select-none"
        @click.prevent="reset"
    >
        <Description class="underline text-2xs" :text="__('advanced-seo::messages.reset_to_default')" />
    </button>
</template>
