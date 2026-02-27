<script setup>
import { computed, markRaw, nextTick, onBeforeUnmount, onMounted, ref, shallowRef, watch } from 'vue';
import { Fieldtype } from '@statamic/cms';
import { CharacterCounter, injectPublishContext } from '@statamic/cms/ui';
import { Editor } from '@tiptap/vue-3';
import Document from '@tiptap/extension-document';
import Paragraph from '@tiptap/extension-paragraph';
import Text from '@tiptap/extension-text';
import History from '@tiptap/extension-history';
import Placeholder from '@tiptap/extension-placeholder';
import Dropcursor from '@tiptap/extension-dropcursor';
import { Extension } from '@tiptap/core';
import { TokenNode } from '../../extensions/TokenNode.js';
import { TokenSuggestion } from '../../extensions/TokenSuggestion.js';
import { parse, stringify } from '../../utils/antlers.js';
import TokenSuggestionList from '../ui/TokenSuggestionList.vue';
import { useSeoValues } from '../../composables/useSeoValues.js';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const fieldtype = Fieldtype.use(emit, props);
defineExpose(fieldtype.expose);

const publishContext = injectPublishContext();
const { resolveAntlers } = useSeoValues();

// ─── Refs ────────────────────────────────────────────────────────────────────

const isInternalUpdate = ref(false);
const isEditorFocused = ref(false);
const suggestionState = ref(null);
const suggestionListEl = ref(null);
const editorEl = ref(null);
const editor = shallowRef(null);

// ─── Computed ────────────────────────────────────────────────────────────────

const tokens = computed(() => props.meta.tokens);

const characterLimit = computed(() => {
    if (publishContext.name.value === 'seo-set-localizations') return null;
    return props.config.character_limit;
});

const suggestionEmpty = computed(() => suggestionState.value && !suggestionState.value.query);

// ─── Editor ─────────────────────────────────────────────────────────────────

const SingleLineDoc = Document.extend({ content: 'paragraph' });

function withInternalUpdate(callback) {
    isInternalUpdate.value = true;
    callback();
    nextTick(() => { isInternalUpdate.value = false; });
}

function collapseRemainingAntlers(instance) {
    const json = instance.getJSON();
    const reparsed = parse(stringify(json), tokens.value);

    if (JSON.stringify(json) !== JSON.stringify(reparsed)) {
        withInternalUpdate(() => {
            instance.commands.setContent(reparsed);
            fieldtype.update(stringify(instance.getJSON()));
        });
    }
}

onMounted(() => {
    editor.value = markRaw(new Editor({
        element: editorEl.value,
        extensions: [
            SingleLineDoc,
            Paragraph,
            Text,
            History,
            Dropcursor.configure({ color: '#3b82f6', width: 2 }),
            Placeholder.configure({ placeholder: __('advanced-seo::messages.token_picker_placeholder') }),
            TokenNode.configure({ tokens: tokens.value }),
            Extension.create({
                name: 'singleLine',
                addKeyboardShortcuts() {
                    return { Enter: () => true };
                },
            }),
            TokenSuggestion.configure({
                tokens: tokens.value,
                suggestion: {
                    render: () => ({
                        onStart: (props) => { suggestionState.value = props; },
                        onUpdate: (props) => { suggestionState.value = props; },
                        onKeyDown: (props) => suggestionListEl.value?.onKeyDown(props) ?? false,
                        onExit: () => { suggestionState.value = null; },
                    }),
                },
            }),
        ],
        content: parse(props.value, tokens.value),
        editable: !fieldtype.isReadOnly.value,
        editorProps: {
            attributes: {
                'data-antlers-input': '',
                class: 'min-w-full text-gray-925 dark:text-gray-300 antialiased text-base leading-[1.375rem] whitespace-nowrap outline-none',
            },
        },
        onUpdate: ({ editor }) => {
            if (isInternalUpdate.value) return;

            withInternalUpdate(() => {
                fieldtype.update(stringify(editor.getJSON()));
            });
        },
        onFocus: () => {
            isEditorFocused.value = true;
            emit('focus');
        },
        onBlur: ({ editor: instance }) => {
            isEditorFocused.value = false;
            collapseRemainingAntlers(instance);
            emit('blur');
        },
    }));
});

onBeforeUnmount(() => editor.value?.destroy());

// ─── Watchers ───────────────────────────────────────────────────────────────

watch(() => props.value, (value) => {
    if (isInternalUpdate.value || !editor.value) return;

    if (suggestionState.value) return;

    const current = stringify(editor.value.getJSON());

    if (value !== current) {
        withInternalUpdate(() => {
            editor.value.commands.setContent(parse(value, tokens.value));
        });
    }
}, { flush: 'post' });
</script>

<template>
    <div
        class="relative bg-white border border-gray-300 rounded-lg appearance-none dark:bg-gray-900 dark:border-gray-700 shadow-ui-sm min-h-10"
        data-ui-input
        :data-suggestion-empty="suggestionEmpty || undefined"
        :style="{ '--suggestion-placeholder': `'${__('advanced-seo::messages.token_suggestion_placeholder')}'` }"
        :class="{
            'pe-9': characterLimit && !fieldtype.isReadOnly.value,
            'border-dashed pointer-events-none': fieldtype.isReadOnly.value,
        }"
    >
        <div class="px-3 py-2 overflow-x-auto [scrollbar-width:none]">
            <div ref="editorEl" />
        </div>

        <div v-if="characterLimit && !fieldtype.isReadOnly.value" class="absolute inset-y-0 flex items-center right-2">
            <CharacterCounter :text="resolveAntlers(props.value)" :limit="characterLimit" />
        </div>

        <TokenSuggestionList
            v-if="suggestionState && isEditorFocused"
            ref="suggestionListEl"
            :items="suggestionState.items"
            :command="suggestionState.command"
        />
    </div>
</template>

<style>
[data-ui-input]:has(.ProseMirror-focused) {
    outline-width: var(--focus-outline-width);
    outline-offset: var(--focus-outline-offset);
    outline-color: var(--focus-outline-color, currentColor);
    outline-style: var(--focus-outline-style, solid);
}

[data-antlers-input] p {
    margin: 0;
}

[data-antlers-input].ProseMirror-focused [data-token].ProseMirror-selectednode {
    background-color: var(--color-blue-100);
}

:is(.dark) [data-antlers-input].ProseMirror-focused [data-token].ProseMirror-selectednode {
    background-color: var(--color-gray-700);
}

[data-antlers-input] p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    color: var(--color-gray-400);
    pointer-events: none;
    float: left;
    height: 0;
}

:is(.dark) [data-antlers-input] p.is-editor-empty:first-child::before {
    color: var(--color-gray-500);
}

[data-antlers-input].ProseMirror-focused .suggestion-active {
    display: inline-flex;
    align-items: center;
    vertical-align: top;
    background-color: var(--color-gray-50);
    box-shadow: inset 0 0 0 1px var(--color-gray-300);
    border-radius: var(--radius-sm);
    color: var(--color-gray-700);
    font-size: var(--text-xs);
    line-height: 1.375rem;
    padding: 0 0.5rem;
}

:is(.dark) [data-antlers-input].ProseMirror-focused .suggestion-active {
    background-color: var(--color-gray-800);
    box-shadow: none;
    color: var(--color-gray-100);
}

[data-suggestion-empty] [data-antlers-input].ProseMirror-focused .suggestion-active::after {
    content: var(--suggestion-placeholder, 'Search …');
    color: var(--color-gray-400);
    pointer-events: none;
}

:is(.dark) [data-suggestion-empty] [data-antlers-input].ProseMirror-focused .suggestion-active::after {
    color: var(--color-gray-500);
}
</style>
