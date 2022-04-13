<template>
    <div>
        <div v-if="this.meta.image">
            <img class="rounded-md" :src="this.meta.image">
        </div>

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
                this.updateImage()
                resolve()
            })
        },

        methods: {
            updateImage() {
                const url = new URL(this.meta.image)

                url.searchParams.delete('timestamp')
                url.searchParams.append('timestamp', Date.now())

                if (this.hasImage(url.href)) {
                    this.meta.image = url.href
                }
            },

            hasImage(url) {
                const http = new XMLHttpRequest();

                http.open('HEAD', url, false);
                http.send();

                return http.status != 404;
            }
        }
    }
</script>
