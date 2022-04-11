<template>
    <div>
        <img v-if="this.meta.image" :src="this.meta.image">

        <div v-else class="p-3 text-center border rounded" style="border-color: #c4ccd4; background-color: #fafcff">
            <small class="mb-0 help-block">{{ this.meta.message }}</small>
        </div>
    </div>
</template>

<script>
    export default {
        mixins: [Fieldtype],

        mounted() {
            Statamic.$hooks.on('entry.saved', (resolve, reject) => {
                const url = new URL(this.meta.image)
                url.searchParams.delete('timestamp')
                url.searchParams.append('timestamp', Date.now())
                this.meta.image = url.href
                resolve();
            });
        },
    }
</script>
