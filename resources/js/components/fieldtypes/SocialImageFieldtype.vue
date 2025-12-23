<template>
    <div>
        <div v-if="exists">
            <img class="rounded-md" :src="image">
        </div>

        <div v-else class="flex items-center justify-center border border-gray-300 border-dashed rounded-lg bg-gray-50 aspect-video dark:bg-gray-900 with-contrast:border-gray-500 dark:border-gray-700">
            <Description :text="meta.message" />
        </div>
    </div>
</template>

<script>
import { FieldtypeMixin as Fieldtype } from '@statamic/cms';
import { Description } from '@statamic/cms/ui';

export default {
    mixins: [Fieldtype],

    components: {
        Description,
    },

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
        'publishContainer.site': function () {
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
