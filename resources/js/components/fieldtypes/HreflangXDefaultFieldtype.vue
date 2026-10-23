<script setup>
import { Fieldtype } from '@statamic/cms';
import { Icon, Select } from '@statamic/cms/ui';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { expose, update } = Fieldtype.use(emit, props);

defineExpose(expose);

// A locked site must not replace the current value. The select still emits that choice, because it ignores option.disabled.
function onUpdate(next) {
    const option = (props.meta.options ?? []).find((option) => option.value === next);

    if (!option || option.disabled) {
        return;
    }

    update(next);
}
</script>

<template>
    <Select
        class="w-full"
        :options="meta.options"
        :clearable="false"
        :model-value="value"
        @update:model-value="onUpdate"
    >
        <template #option="option">
            <span class="flex items-center w-full min-w-0 gap-2">
                <Icon
                    v-if="option.disabled"
                    name="padlock-locked"
                    class="size-3.5 shrink-0"
                    :class="option.value === value ? '' : 'text-gray-300 dark:text-gray-500'"
                />
                <span
                    class="truncate"
                    :class="option.disabled && option.value !== value ? 'text-gray-300 dark:text-gray-500' : ''"
                >
                    {{ __(option.label) }}
                </span>
            </span>
            <span
                v-if="option.disabled"
                class="absolute inset-0 cursor-not-allowed"
                @pointerdown.stop.prevent
                @pointerup.stop.prevent
                @click.stop.prevent
            />
        </template>
    </Select>
</template>
