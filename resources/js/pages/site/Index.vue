<script setup>
import { Head, Link } from '@statamic/cms/inertia';
import { Header, CardPanel, Heading, Subheading, Icon } from '@statamic/cms/ui';

defineProps({
    title: String,
    icon: String,
    items: Array,
    columns: Array,
});
</script>

<template>
    <Head :title />

    <div class="max-w-5xl mx-auto">
        <Header :title icon="web" />

        <CardPanel>
			<div class="flex flex-wrap starting-style-transition-children">
                <Link
                    v-for="item in items"
                    :key="item.handle"
                    :href="item.localization_url"
                    class="items-start w-full px-2 py-3 border border-transparent rounded-md group lg:p-4 hover:bg-gray-100 dark:hover:border-dark-400 dark:hover:bg-dark-575 md:flex lg:w-1/2"
                >
                    <Icon :name="item.icon" class="mt-1 mb-2 text-gray-400 size-6 me-4"/>
                    <div class="flex-1 md:me-6">
						<Heading size="lg" :text="__(`advanced-seo::messages.${item.handle}`)" />
						<Subheading v-text="__(`advanced-seo::messages.${item.handle}_description`)" />
					</div>
                </Link>
			</div>
		</CardPanel>
    </div>
</template>
