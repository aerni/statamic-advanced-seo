<script setup>
import { computed } from 'vue';

const props = defineProps({
    count: { type: Number, required: true },
    limit: { type: Number, required: true },
    approximate: { type: Boolean, default: false },
});

const color = computed(() => {
    if (props.approximate) return 'text-gray-400 dark:text-gray-500';

    const ratio = props.count / props.limit;
    if (ratio >= 0.95) return 'text-red-500';
    if (ratio >= 0.8) return 'text-amber-500';
    return 'text-gray-400 dark:text-gray-500';
});
</script>

<template>
    <span class="absolute right-0 mt-2 select-none top-full text-2xs tabular-nums" :class="color">
        <template v-if="approximate">~{{ count }}</template>
        <template v-else>{{ count }}/{{ limit }}</template>
    </span>
</template>
