<script setup>
import { computed } from 'vue';
import { Fieldtype } from '@statamic/cms';
import { Input, Select, injectPublishContext } from '@statamic/cms/ui';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { name, isReadOnly, update, expose } = Fieldtype.use(emit, props);

defineExpose(expose);

const publishContext = injectPublishContext();

const siteOptions = computed(() =>
    Object.entries(props.meta.sites).map(([value, label]) => ({ value, label })),
);

const selectedSite = computed({
    get: () => publishContext?.values?.value?.site ?? props.meta.defaultSite,
    set: (value) => publishContext?.setFieldValue?.('site', value),
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
    <div class="flex gap-2">
        <div v-if="meta.multisite" class="w-fit">
            <Select :options="siteOptions" v-model="selectedSite" :read-only="isReadOnly" />
        </div>
        <div class="flex-1">
            <Input
                :model-value="value"
                :focus="config.focus"
                :read-only="isReadOnly"
                :placeholder="config.placeholder"
                :name="name"
                :id="id"
                @update:model-value="onInput"
                @focus="$emit('focus')"
                @blur="$emit('blur')"
            />
        </div>
    </div>
</template>
