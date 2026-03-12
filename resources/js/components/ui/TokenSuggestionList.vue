<script setup>
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';

const props = defineProps({
    items: { type: Array, required: true },
});

const selectedIndex = ref(0);
const isKeyboardNav = ref(false);
const listRef = useTemplateRef('list');

const selectableItems = computed(() => props.items.filter(item => item.group !== 'header'));

watch(() => props.items, () => {
    selectedIndex.value = 0;
}, { immediate: true });

function scrollToSelected() {
    nextTick(() => {
        const selected = selectableItems.value[selectedIndex.value];
        if (!selected) return;

        const index = props.items.indexOf(selected);

        listRef.value?.children[index]?.scrollIntoView({ block: 'nearest' });

        if (index > 0 && props.items[index - 1]?.group === 'header') {
            listRef.value?.children[index - 1]?.scrollIntoView({ block: 'nearest' });
        }
    });
}

function isSelected(item) {
    return selectableItems.value[selectedIndex.value] === item;
}

function selectItem(item) {
    if (isKeyboardNav.value) return;

    const index = selectableItems.value.indexOf(item);
    if (index !== -1) selectedIndex.value = index;
}

function onMouseMove() {
    isKeyboardNav.value = false;
}

defineExpose({
    onKeyDown({ event }) {
        if (!selectableItems.value.length) {
            return event.key === 'ArrowUp' || event.key === 'ArrowDown' || event.key === 'Enter';
        }

        if (event.key === 'ArrowUp') {
            isKeyboardNav.value = true;
            selectedIndex.value = selectedIndex.value <= 0
                ? selectableItems.value.length - 1
                : selectedIndex.value - 1;
            scrollToSelected();
            return true;
        }

        if (event.key === 'ArrowDown') {
            isKeyboardNav.value = true;
            selectedIndex.value = selectedIndex.value >= selectableItems.value.length - 1
                ? 0
                : selectedIndex.value + 1;
            scrollToSelected();
            return true;
        }

        if (event.key === 'Enter') {
            selectableItems.value[selectedIndex.value]?.onSelect();
            return true;
        }

        return false;
    },
});
</script>

<template>
    <div
        class="absolute -inset-x-px top-[calc(100%+0.25rem)] z-50 select-none rounded-xl
               bg-white dark:bg-gray-850 border border-gray-200 dark:border-black
               shadow-lg overflow-hidden"
        @mousedown.prevent
        @mousemove="onMouseMove"
    >
        <div ref="list" role="listbox" class="flex flex-col max-h-64 overflow-y-auto p-1.5" style="scroll-padding: 0.375rem">
            <template v-for="(item, index) in items" :key="item.id ?? item.handle ?? item.label">
                <!-- Action -->
                <button
                    v-if="item.group === 'actions'"
                    role="option"
                    :aria-selected="isSelected(item)"
                    type="button"
                    class="flex items-center gap-2 cursor-default rounded-lg px-2.5 py-1.5 w-full text-left text-sm"
                    :class="isSelected(item)
                        ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-300'
                        : 'text-gray-700 dark:text-gray-400'"
                    @mouseenter="selectItem(item)"
                    @click="item.onSelect()"
                >
                    <svg class="size-4 shrink-0" :class="isSelected(item) ? 'text-yellow-500' : 'text-gray-700 dark:text-gray-400'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M4.952 2.269c-.449-.449-.518-.86-.844-1.275a.601.601 0 0 0-.994 0c-.326.415-.396.826-.844 1.275-.45.449-.861.518-1.276.845a.601.601 0 0 0 0 .994c.415.326.827.396 1.276.844.449.449.518.861.844 1.276a.601.601 0 0 0 .994 0c.326-.415.396-.827.844-1.276.449-.449.861-.518 1.276-.844a.601.601 0 0 0 0-.994c-.415-.326-.827-.396-1.276-.845Z" />
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M11.048 7.16c-.65-.651-.751-1.248-1.224-1.849a.866.866 0 0 0-1.44 0c-.473.601-.574 1.198-1.224 1.849-.651.65-1.248.751-1.849 1.224a.866.866 0 0 0 0 1.44c.601.473 1.198.573 1.849 1.224.65.65.751 1.247 1.224 1.849a.866.866 0 0 0 1.44 0c.473-.601.573-1.198 1.224-1.849.65-.65 1.247-.751 1.849-1.224a.866.866 0 0 0 0-1.44c-.602-.473-1.199-.573-1.849-1.224Z" />
                    </svg>
                    <span>{{ __('advanced-seo::messages.ai_generate') }}</span>
                </button>

                <!-- Group header -->
                <div
                    v-else-if="item.group === 'header'"
                    class="px-2.5 pb-1 text-2xs font-medium text-gray-500 dark:text-gray-500"
                    :class="index === 0
                        ? 'pt-1'
                        : '-mx-1.5 px-4 mt-1.5 border-t border-gray-100 dark:border-gray-700 pt-3'"
                >
                    {{ item.label }}
                </div>

                <!-- Token -->
                <button
                    v-else
                    :id="`token-option-${item.handle}`"
                    role="option"
                    :aria-selected="isSelected(item)"
                    type="button"
                    class="cursor-default rounded-lg px-2.5 py-1.5 w-full text-left text-sm"
                    :class="isSelected(item)
                        ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-300'
                        : 'text-gray-700 dark:text-gray-400'"
                    @mouseenter="selectItem(item)"
                    @click="item.onSelect()"
                >
                    {{ item.display }}
                </button>
            </template>

            <div v-if="!items.length" class="px-2.5 py-1.5 text-center text-sm text-gray-600">
                {{ __('advanced-seo::messages.no_results') }}
            </div>
        </div>
    </div>
</template>
