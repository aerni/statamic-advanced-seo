<script setup>
import { Head, Link } from '@statamic/cms/inertia';
import { Header, Panel, DocsCallout, Icon } from '@statamic/cms/ui';

defineProps({
    defaults: Array,
    title: String
});
</script>

<template>
    <Head :title />

    <div class="max-w-5xl mx-auto">
        <Header :title :icon="defaults[0].icon" />

        <section class="space-y-6 starting-style-transition-children">
            <Panel>
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>{{ __('Taxonomy') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="taxonomy in defaults" :key="taxonomy.handle">
                        <td>
                            <div class="flex items-center gap-2">
                                <Icon :name="taxonomy.icon" class="text-gray-500 me-1" />
                                <Link :href="cp_url(`advanced-seo/taxonomies/${taxonomy.handle}`)" v-text="__(taxonomy.title)" />
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </Panel>
		</section>

        <DocsCallout :topic="__('Taxonomy Defaults')" url="https://advanced-seo.michaelaerni.ch/usage/settings-and-defaults" />
    </div>
</template>
