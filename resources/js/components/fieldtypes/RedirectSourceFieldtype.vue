<script setup>
import { computed } from 'vue';
import { Fieldtype } from '@statamic/cms';
import { Input, injectPublishContext } from '@statamic/cms/ui';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { name, isReadOnly, update, expose } = Fieldtype.use(emit, props);

defineExpose(expose);

const publishContext = injectPublishContext();

const siteUrl = computed(() => {
    const site = publishContext?.values?.value?.site ?? props.meta.defaultSite;

    return site ? (props.meta.sites[site] ?? null) : null;
});

const displayValue = computed(() => {
    if (!props.value) {
        return props.value;
    }

    if (props.value.startsWith('#')) {
        return props.value;
    }

    return props.value.replace(/^\//, '');
});

function onInput(typed) {
    if (!typed) {
        return update(typed);
    }

    if (typed.startsWith('#')) {
        return update(typed);
    }

    update('/' + typed.replace(/^\/+/, ''));
}
</script>

<template>
    <Input
        :model-value="displayValue"
        :focus="config.focus"
        :read-only="isReadOnly"
        :prepend="siteUrl ? siteUrl + '/' : null"
        :placeholder="config.placeholder"
        :name="name"
        :id="id"
        @update:model-value="onInput"
        @focus="$emit('focus')"
        @blur="$emit('blur')"
    />
</template>
