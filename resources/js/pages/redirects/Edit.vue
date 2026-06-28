<script setup>
import { onMounted, onUnmounted, ref, useTemplateRef } from 'vue';
import { PublishContainer, PublishTabs, Header, Button, Panel, Heading, Switch, StatusIndicator } from '@statamic/cms/ui';
import { Pipeline, Request } from '@statamic/cms/save-pipeline';
import { Head, router } from '@statamic/cms/inertia';

const props = defineProps({
    title: { type: String, required: true },
    blueprint: { type: Object, required: true },
    values: { type: Object, required: true },
    meta: { type: Object, required: true },
    enabled: { type: Boolean, default: true },
    submitUrl: { type: String, required: true },
});

const container = useTemplateRef('container');
const values = ref(props.values);
const meta = ref(props.meta);
const enabled = ref(props.enabled);
const errors = ref({});
const saving = ref(false);

function save() {
    new Pipeline()
        .provide({ container, errors, saving })
        .through([
            new Request(props.submitUrl, 'patch', { enabled: enabled.value }),
        ])
        .then((response) => {
            Statamic.$toast.success(__('Saved'));
            router.get(response.data.redirect);
        });
}

let saveKeyBinding;

onMounted(() => {
    saveKeyBinding = Statamic.$keys.bindGlobal(['mod+s'], (e) => {
        e.preventDefault();
        save();
    });
});

onUnmounted(() => saveKeyBinding.destroy());
</script>

<template>
    <Head :title />

    <Header>
        <template #title>
            <StatusIndicator :status="enabled ? 'published' : 'draft'" />
            {{ values.source ?? title }}
        </template>
        <Button variant="primary" :text="__('Save')" :disabled="saving" @click="save" />
    </Header>

    <PublishContainer
        ref="container"
        name="redirect-edit"
        :blueprint
        :meta
        :errors
        v-model="values"
    >
        <PublishTabs>
            <template #actions>
                <Panel class="flex justify-between px-5! py-3! dark:bg-gray-800!">
                    <Heading :text="__('advanced-seo::fields.redirect_enabled.display')" />
                    <Switch v-model="enabled" />
                </Panel>
            </template>
        </PublishTabs>
    </PublishContainer>
</template>
