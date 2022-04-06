<template>
    <div>
        <img v-if="this.meta.image" :src="this.meta.image">

        <div v-else class="p-3 text-center border rounded" style="border-color: #c4ccd4; background-color: #fafcff">
            <small class="mb-0 help-block">Save the entry to generate your first {{ this.meta.title }} image.</small>
        </div>
    </div>
</template>

<script>
    export default {
        mixins: [Fieldtype],
    }
</script>
