<template>
    <div>
        <div v-if="this.image">
            <img class="rounded-md" :src="this.image">
        </div>

        <div v-else class="p-3 text-center border rounded" style="border-color: #c4ccd4; background-color: #fafcff">
            <small class="mb-0 help-block">{{ this.meta.message }}</small>
        </div>
    </div>
</template>

<script>
    export default {
        mixins: [Fieldtype],

        data() {
            return {
                image: this.meta.image
            }
        },

        mounted() {
            Statamic.$hooks.on('entry.saved', (resolve) => {
                if (this.image) this.image = `${this.image}?reload`
                resolve();
            })
        },

        watch: {
            '$store.state.publish.base.site': function () {
                this.image = this.meta.image
            },
        },
    }
</script>
