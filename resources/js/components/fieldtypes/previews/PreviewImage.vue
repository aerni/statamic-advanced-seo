<script setup>
import { computed } from 'vue';
import { Icon } from '@statamic/cms/ui';

const props = defineProps({
    image: { type: Object, default: null },
    width: { type: Number, required: true },
    height: { type: Number, required: true },
});

const aspectRatio = computed(() => `${props.width} / ${props.height}`);
const focalPoint = computed(() => props.image?.objectPosition ?? '50% 50%');
</script>

<template>
    <div class="w-full overflow-hidden bg-gray-100 dark:bg-gray-800" :style="{ aspectRatio }">
        <img v-if="image" :src="image.url" class="object-cover size-full" :style="{ objectPosition: focalPoint }" />
        <div v-else class="flex items-center justify-center size-full">
            <Icon name="assets" class="text-gray-300 size-12 dark:text-gray-600" />
        </div>
    </div>
</template>
