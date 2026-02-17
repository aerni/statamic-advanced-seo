<script setup>
import { computed } from 'vue';
import { Icon } from '@statamic/cms/ui';

const props = defineProps({
    image: { type: Object, default: null },
    preset: { type: Object, required: true },
});

const aspectRatio = computed(() => `${props.preset.width} / ${props.preset.height}`);

/**
 * Compute the background-image style that replicates Glide's crop_focal behavior.
 *
 * Uses the same algorithm as Statamic's FocalPointPreviewFrame: center the visible
 * area on the focal point, clamped to image edges. Expressed as CSS percentages so
 * no DOM measurement is needed — the math depends only on aspect ratios.
 */
const imageStyle = computed(() => {
    if (!props.image) return {};

    const { url, x: focalX = 50, y: focalY = 50, z: zoom = 1, width: imageWidth, height: imageHeight } = props.image;

    if (!imageWidth || !imageHeight) {
        return {
            backgroundImage: `url('${encodeURI(url)}')`,
            backgroundSize: 'cover',
            backgroundPosition: `${focalX}% ${focalY}%`,
            transform: `scale(${zoom})`,
            transformOrigin: `${focalX}% ${focalY}%`,
        };
    }

    const imageRatio = imageWidth / imageHeight;
    const frameRatio = props.preset.width / props.preset.height;

    // Frame dimensions as a percentage of the cover-fitted image dimensions.
    const visibleWidth = imageRatio > frameRatio ? (frameRatio / imageRatio) * 100 : 100;
    const visibleHeight = imageRatio > frameRatio ? 100 : (imageRatio / frameRatio) * 100;

    // Center the visible frame on the focal point, clamped to image edges.
    const offsetLeft = Math.max(0, Math.min(focalX - visibleWidth / 2, 100 - visibleWidth));
    const offsetTop = Math.max(0, Math.min(focalY - visibleHeight / 2, 100 - visibleHeight));

    // Convert the clamped offsets to CSS background-position percentages.
    const backgroundX = visibleWidth < 100 ? (offsetLeft * 100 / (100 - visibleWidth)) : 50;
    const backgroundY = visibleHeight < 100 ? (offsetTop * 100 / (100 - visibleHeight)) : 50;

    // Transform origin: focal point's position within the visible frame.
    const originX = (focalX - offsetLeft) / visibleWidth * 100;
    const originY = (focalY - offsetTop) / visibleHeight * 100;

    return {
        backgroundImage: `url('${encodeURI(url)}')`,
        backgroundSize: 'cover',
        backgroundPosition: `${backgroundX}% ${backgroundY}%`,
        transform: `scale(${zoom})`,
        transformOrigin: `${originX}% ${originY}%`,
    };
});
</script>

<template>
    <div class="relative w-full overflow-hidden bg-gray-100 dark:bg-gray-800" :style="{ aspectRatio }">
        <div v-if="image" class="size-full" :style="imageStyle" />
        <div v-else class="flex items-center justify-center size-full">
            <Icon name="assets" class="text-gray-300 size-12 dark:text-gray-600" />
        </div>
    </div>
</template>
