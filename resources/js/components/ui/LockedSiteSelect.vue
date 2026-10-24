<script setup>
import { Icon, Select } from '@statamic/cms/ui';

const props = defineProps({
    options: { type: Array, default: () => [] },
    modelValue: { type: [String, null], default: null },
    clearable: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

// A locked site must not replace the current value. The select still emits that choice, because it ignores option.disabled.
function onUpdate(next) {
    const option = props.options.find((option) => option.value === next);

    if (option?.disabled) {
        return;
    }

    emit('update:modelValue', next);
}
</script>

<template>
    <Select
        class="w-full"
        :options
        :clearable
        :disabled
        :model-value="modelValue"
        @update:model-value="onUpdate"
    >
        <template #selected-option="{ option }">
            <span class="truncate min-w-0">{{ __(option.label) }}</span>
            <Icon
                v-if="option.disabled"
                name="padlock-locked"
                class="size-3.5 shrink-0 opacity-50"
            />
        </template>
        <template #option="option">
            <span
                class="flex items-center w-full min-w-0 gap-2"
                :class="option.disabled && option.value !== modelValue ? 'text-gray-300 dark:text-gray-500' : ''"
            >
                <span class="truncate min-w-0">
                    {{ __(option.label) }}
                </span>
                <Icon
                    v-if="option.disabled"
                    name="padlock-locked"
                    class="size-3.5 shrink-0 opacity-50"
                />
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
