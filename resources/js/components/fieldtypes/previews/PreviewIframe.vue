<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    src: { type: String, required: true },
    preset: { type: Object, required: true },
    crop: { type: Object, default: null },
});

const aspectRatio = computed(() => {
    const { width, height } = props.crop ?? props.preset;
    return `${width} / ${height}`;
});
const container = ref(null);
const containerSize = ref({ width: 0, height: 0 });

const iframeStyle = computed(() => {
    const { width, height } = containerSize.value;

    if (!width || !height) return { visibility: 'hidden' };

    const scale = Math.max(
        width / props.preset.width,
        height / props.preset.height,
    );

    const left = (width - props.preset.width * scale) / 2;
    const top = (height - props.preset.height * scale) / 2;

    return {
        width: `${props.preset.width}px`,
        height: `${props.preset.height}px`,
        transform: `translate(${left}px, ${top}px) scale(${scale})`,
        transformOrigin: '0 0',
    };
});

let observer;

onMounted(() => {
    observer = new ResizeObserver((entries) => {
        const { width, height } = entries[0].contentRect;
        containerSize.value = { width, height };
    });

    observer.observe(container.value);
});

onUnmounted(() => observer.disconnect());
</script>

<template>
    <div ref="container" class="relative w-full overflow-hidden bg-gray-100 dark:bg-gray-800" :style="{ aspectRatio }">
        <iframe :src :style="iframeStyle" class="absolute bg-white border-0 pointer-events-none" />
    </div>
</template>
