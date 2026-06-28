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
    const site = publishContext?.values?.value?.site;

    return site ? (props.meta.sites[site] ?? null) : null;
});

function onInput(value) {
    if (!value) {
        return update(value);
    }

    if (siteUrl.value && value.startsWith(siteUrl.value)) {
        value = value.substring(siteUrl.value.length);
    }

    if (!value.startsWith('/')) {
        value = '/' + value;
    }

    update(value);
}
</script>

<template>
    <Input
        :model-value="value"
        :focus="config.focus"
        :read-only="isReadOnly"
        :prepend="siteUrl"
        :placeholder="config.placeholder"
        :name="name"
        :id="id"
        @update:model-value="onInput"
        @focus="$emit('focus')"
        @blur="$emit('blur')"
    />
</template>
