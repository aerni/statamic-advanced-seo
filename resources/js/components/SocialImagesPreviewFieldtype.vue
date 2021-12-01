<template>
    <div>
        <img :src="this.meta.image">
    </div>
</template>

<script>
    export default {
        name: 'social-images-preview-fieldtype',
        mixins: [Fieldtype],
    }
</script>
