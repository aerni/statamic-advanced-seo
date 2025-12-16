<script setup>
import { Fieldtype } from '@statamic/cms';
import { Select } from '@statamic/cms/ui';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { expose, update } = Fieldtype.use(emit, props);
defineExpose(expose);
</script>

<template>
    <Select
        :options="props.meta"
        :clearable="true"
        :model-value="props.value"
        @update:model-value="update"
    />
</template>
