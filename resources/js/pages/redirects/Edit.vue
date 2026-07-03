<script setup>
import { computed, onMounted, onUnmounted, ref, useTemplateRef } from 'vue';
import { PublishContainer, PublishTabs, Header, Button, Panel, Heading, Switch, StatusIndicator, Badge } from '@statamic/cms/ui';
import { Pipeline, Request } from '@statamic/cms/save-pipeline';
import { Head, router } from '@statamic/cms/inertia';

const props = defineProps({
    title: { type: String, required: true },
    blueprint: { type: Object, required: true },
    values: { type: Object, required: true },
    meta: { type: Object, required: true },
    enabled: { type: Boolean, default: true },
    submitUrl: { type: String, required: true },
    testUrl: { type: String, default: null },
    hits: { type: Object, default: null },
});

const container = useTemplateRef('container');
const values = ref(props.values);
const meta = ref(props.meta);
const enabled = ref(props.enabled);
const errors = ref({});
const saving = ref(false);

const heading = props.values.source ?? props.title;

const saveText = computed(() => {
    if (enabled.value === props.enabled) {
        return __('Save');
    }

    return enabled.value
        ? __('advanced-seo::messages.save_and_enable')
        : __('advanced-seo::messages.save_and_disable');
});

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
    <Head :title="heading" />

    <Header>
        <template #title>
            <StatusIndicator :status="enabled ? 'published' : 'draft'" />
            {{ heading }}
        </template>
        <Button
            v-if="testUrl && props.enabled && Number(values.response_code) !== 410"
            :href="testUrl"
            :text="__('advanced-seo::messages.test_redirect')"
            icon="external-link"
            target="_blank"
            rel="noopener noreferrer"
        />
        <Button variant="primary" :text="saveText" :disabled="saving" @click="save" />
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
                <Panel class="px-5! py-3! dark:bg-gray-800!">
                    <div class="flex items-center justify-between">
                        <Heading :text="__('advanced-seo::fields.redirect_enabled.display')" />
                        <Switch v-model="enabled" />
                    </div>
                    <div v-if="hits" class="flex items-center gap-1.5 mt-2">
                        <Badge icon="cursor-click" :text="hits.count" :title="__('advanced-seo::messages.redirect_hits')" pill />
                        <Badge v-if="hits.last_hit_at" icon="time-clock" :title="__('advanced-seo::messages.redirect_last_hit_at')" pill>
                            <date-time :of="hits.last_hit_at" />
                        </Badge>
                    </div>
                </Panel>
            </template>
        </PublishTabs>
    </PublishContainer>
</template>
