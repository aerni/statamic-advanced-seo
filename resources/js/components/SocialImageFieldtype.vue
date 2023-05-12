<template>
    <div>
        <div v-if="this.exists">
            <img class="rounded-md" :src="this.image">
        </div>

        <div v-else class="p-3 text-center bg-gray-200 border rounded">
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
                resolve()
            })
        },

        watch: {
            '$store.state.publish.base.site': function () {
                this.image = this.meta.image
            },
        },

        computed: {
            exists() {
                const http = new XMLHttpRequest();

                http.open('HEAD', this.image, false);
                http.send();

                return http.status != 404;
            }
        },
    }
</script>
