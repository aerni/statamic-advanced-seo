<script setup>
import { computed, h } from 'vue';
import { IndexFieldtype } from '@statamic/cms';

const props = defineProps(IndexFieldtype.props);

const text = computed(() => {
    const value = props.value.value;

    if (value === 0) return '0';
    if (!value) return '';
    if (typeof value !== 'string') return JSON.stringify(value);

    return value.length > 50 ? `${value.substring(0, 50)}…` : value;
});

// Text fallback for child fieldtypes without a registered index component.
const fallback = { inheritAttrs: false, render: () => h('div', text.value) };

const component = computed(() => {
    return Statamic.$app.component(props.value.component) ?? fallback;
});
</script>

<template>
    <component :is="component" :handle="handle" :value="value.value" :values="values" />
</template>
