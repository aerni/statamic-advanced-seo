<script setup>
import { Head, Link } from '@statamic/cms/inertia';
import { Header, CardPanel, Heading, Subheading, DocsCallout, Icon } from '@statamic/cms/ui';

defineProps({
    defaults: Array,
    title: String
});
</script>

<template>
    <Head :title />

    <div class="max-w-5xl mx-auto">
        <Header :title icon="web" />

        <CardPanel>
			<div class="flex flex-wrap starting-style-transition-children">
                <Link
                    v-for="siteDefault in defaults"
                    :key="siteDefault.handle"
                    :href="cp_url(`advanced-seo/site/${siteDefault.handle}`)"
                    class="items-start w-full p-4 border border-transparent rounded-md group hover:bg-gray-100 dark:hover:border-dark-400 dark:hover:bg-dark-575 md:flex lg:w-1/2"
                >
                    <Icon :name="siteDefault.icon" class="mt-1 mb-2 text-gray-400 size-6 me-4"/>
                    <div class="flex-1 mb-4 md:mb-0 md:me-6">
						<Heading size="lg" :text="__(`advanced-seo::messages.${siteDefault.handle}`)" />
						<Subheading v-text="__(`advanced-seo::messages.${siteDefault.handle}_description`)" />
					</div>
                </Link>
			</div>
		</CardPanel>

        <DocsCallout :topic="__('Site Defaults')" url="https://advanced-seo.michaelaerni.ch/usage/settings-and-defaults" />
    </div>
</template>
