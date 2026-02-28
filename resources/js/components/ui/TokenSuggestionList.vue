<script setup>
import { nextTick, ref, useTemplateRef, watch } from 'vue';

const props = defineProps({
    items: { type: Array, required: true },
    command: { type: Function, required: true },
});

const selectedIndex = ref(0);
const listRef = useTemplateRef('list');

watch(() => props.items, () => {
    selectedIndex.value = 0;
});

function scrollToSelected() {
    nextTick(() => {
        listRef.value?.children[selectedIndex.value]?.scrollIntoView({ block: 'nearest' });
    });
}

function selectItem(index) {
    const item = props.items[index];

    if (item) {
        props.command(item);
    }
}

defineExpose({
    onKeyDown({ event }) {
        if (!props.items.length) {
            return event.key === 'ArrowUp' || event.key === 'ArrowDown' || event.key === 'Enter';
        }

        if (event.key === 'ArrowUp') {
            selectedIndex.value = (selectedIndex.value + props.items.length - 1) % props.items.length;
            scrollToSelected();
            return true;
        }

        if (event.key === 'ArrowDown') {
            selectedIndex.value = (selectedIndex.value + 1) % props.items.length;
            scrollToSelected();
            return true;
        }

        if (event.key === 'Enter') {
            selectItem(selectedIndex.value);
            return true;
        }

        return false;
    },
});
</script>

<template>
    <div
        class="absolute -inset-x-px top-[calc(100%+0.5rem)] z-50 select-none rounded-xl
               bg-white dark:bg-gray-850 border border-gray-200 dark:border-black
               shadow-lg overflow-hidden"
        @mousedown.prevent
    >
        <div ref="list" role="listbox" class="flex flex-col max-h-64 overflow-y-auto p-1.5" style="scroll-padding: 0.375rem">
            <template v-if="items.length">
                <button
                    v-for="(field, index) in items"
                    :id="`token-option-${field.handle}`"
                    :key="field.handle"
                    role="option"
                    :aria-selected="index === selectedIndex"
                    type="button"
                    class="flex items-center justify-between gap-4 cursor-pointer rounded-lg px-2.5 py-1.5 w-full text-left text-sm antialiased text-gray-900 dark:text-gray-300"
                    :class="index === selectedIndex
                        ? 'bg-gray-100 dark:bg-gray-800'
                        : 'hover:bg-gray-100 dark:hover:bg-gray-800'"
                    @click="selectItem(index)"
                >
                    <span class="truncate">{{ field.display }}</span>
                    <span class="text-xs text-gray-500 shrink-0 dark:text-gray-500">{{ field.handle }}</span>
                </button>
            </template>

            <div v-else class="px-2.5 py-1.5 text-center text-sm text-gray-600">
                {{ __('advanced-seo::messages.no_results') }}
            </div>
        </div>
    </div>
</template>
