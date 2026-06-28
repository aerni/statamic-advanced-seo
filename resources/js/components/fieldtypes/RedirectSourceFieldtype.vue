<script setup>
import { computed } from 'vue';
import { Fieldtype } from '@statamic/cms';
import { Badge, Input, injectPublishContext } from '@statamic/cms/ui';

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

const matchType = computed(() => {
    if (!props.value) {
        return null;
    }

    if (props.value.startsWith('#')) {
        return 'regex';
    }

    if (props.value.includes('*')) {
        return 'wildcard';
    }

    return 'exact';
});

const matchTypeLabel = computed(() => {
    if (!matchType.value) {
        return null;
    }

    return __(`advanced-seo::messages.match_type_${matchType.value}`);
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
    <div class="flex items-center gap-2">
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
        <Badge v-if="matchType" :text="matchTypeLabel" />
    </div>
</template>
