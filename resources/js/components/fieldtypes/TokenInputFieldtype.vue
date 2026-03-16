<script setup>
import { computed, getCurrentInstance, markRaw, nextTick, onBeforeUnmount, onMounted, ref, shallowRef, watch } from 'vue';
import { Fieldtype } from '@statamic/cms';
import { Button, injectPublishContext } from '@statamic/cms/ui';
import { Editor } from '@tiptap/vue-3';
import { NodeSelection } from '@tiptap/pm/state';
import Document from '@tiptap/extension-document';
import Paragraph from '@tiptap/extension-paragraph';
import Text from '@tiptap/extension-text';
import History from '@tiptap/extension-history';
import Placeholder from '@tiptap/extension-placeholder';
import Dropcursor from '@tiptap/extension-dropcursor';
import { SingleLine } from '../../extensions/SingleLine.js';
import { TokenNode } from '../../extensions/TokenNode.js';
import { TokenSelectionHighlight } from '../../extensions/TokenSelectionHighlight.js';
import { TokenSuggestion } from '../../extensions/TokenSuggestion.js';
import { ANTLERS_PATTERN, normalizeHandle, parse, stringify } from '../../utils/antlers.js';
import { useSeoValues } from '../../composables/useSeoValues.js';
import CharacterCounter from '../ui/CharacterCounter.vue';
import TokenSuggestionList from '../ui/TokenSuggestionList.vue';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const fieldtype = Fieldtype.use(emit, props);
defineExpose(fieldtype.expose);

const publishContext = injectPublishContext();
const { resolveAntlers } = useSeoValues();
const { $axios } = getCurrentInstance().appContext.config.globalProperties;

// ─── Refs ────────────────────────────────────────────────────────────────────

const isInternalUpdate = ref(false);
const isEditorFocused = ref(false);
const suggestionState = ref(null);
const suggestionListEl = ref(null);
const editorEl = ref(null);
const editor = shallowRef(null);
const loading = ref(false);

// ─── Computed ────────────────────────────────────────────────────────────────

const field = computed(() => props.handle.replace(/_child$/, ''));

const actions = computed(() => {
    if (publishContext.name.value === 'seo-set-localizations') return [];
    return props.meta.actions;
});

const tokens = computed(() => props.meta.tokens);

const items = computed(() => {
    if (!suggestionState.value) return [];

    const result = [];

    if (!suggestionState.value.query) {
        for (const action of actions.value) {
            result.push({ ...action, group: 'actions', onSelect: () => generateWithAi() });
        }
    }

    let lastGroup = null;

    for (const item of suggestionState.value.items) {
        if (item.group !== lastGroup) {
            result.push({ group: 'header', label: item.group });
            lastGroup = item.group;
        }

        result.push({ ...item, onSelect: () => suggestionState.value.command(item) });
    }

    return result;
});

const characterLimit = computed(() => {
    if (publishContext.name.value === 'seo-set-localizations') return null;
    return props.config.character_limit;
});

const characterCount = computed(() => {
    const resolved = resolveAntlers(props.value) ?? '';
    const hasUnresolvable = antlersPatternGlobal.test(resolved);

    antlersPatternGlobal.lastIndex = 0;

    return {
        count: hasUnresolvable ? resolved.replace(antlersPatternGlobal, '').length : resolved.length,
        approximate: hasUnresolvable,
    };
});

// ─── Actions ────────────────────────────────────────────────────────────────

function openTokenSuggestion() {
    if (!editor.value) return;

    if (suggestionState.value) {
        editor.value.chain().focus().deleteRange(suggestionState.value.range).run();
        return;
    }

    const text = stringify(editor.value.getJSON());
    const content = text.length > 0 && !text.endsWith(' ') ? ' /' : '/';

    const end = editor.value.state.doc.content.size - 1;

    editor.value.chain().focus().setTextSelection(end).insertContent(content).run();
}

async function generateWithAi() {
    if (loading.value) return;

    if (suggestionState.value) {
        editor.value.chain().focus().deleteRange(suggestionState.value.range).run();
    }

    loading.value = true;
    editor.value.setEditable(false);

    try {
        const response = await $axios.post(cp_url('advanced-seo/ai/generate'), {
            field: field.value,
            blueprint: publishContext.blueprint.value.fqh,
            site: publishContext.site.value,
            content: publishContext.values.value,
        });

        const text = response.data.replace(/\n+/g, ' ').trim();

        withInternalUpdate(() => {
            editor.value.commands.setContent(parse(text));
            fieldtype.update(text);
        });
    } catch (error) {
        const message = error.response?.data?.error ?? error.message;
        console.error('[Advanced SEO]', message, ...(error.response?.data?.reason ? [error.response.data.reason] : []));
        Statamic.$toast.error(message);
    } finally {
        editor.value.setEditable(true);
        loading.value = false;
    }
}

// ─── Editor ─────────────────────────────────────────────────────────────────

const SingleLineDoc = Document.extend({ content: 'paragraph' });
const antlersPatternGlobal = new RegExp(ANTLERS_PATTERN.source, 'g');

function withInternalUpdate(callback) {
    isInternalUpdate.value = true;
    try {
        callback();
    } finally {
        isInternalUpdate.value = false;
    }
}

function collapseRemainingAntlers(instance, { selectCollapsed = false } = {}) {
    const { doc, schema } = instance.state;
    const replacements = [];

    doc.descendants((node, pos) => {
        if (!node.isText) return;

        for (const match of node.text.matchAll(antlersPatternGlobal)) {
            const handle = normalizeHandle(match[1]);
            if (!handle) continue;

            replacements.push({
                from: pos + match.index,
                to: pos + match.index + match[0].length,
                handle,
            });
        }
    });

    if (replacements.length === 0) return false;

    withInternalUpdate(() => {
        const tr = instance.state.tr;

        for (let i = replacements.length - 1; i >= 0; i--) {
            const { from, to, handle } = replacements[i];
            tr.replaceWith(from, to, schema.nodes.token.create({ handle }));
        }

        if (selectCollapsed && replacements.length === 1) {
            tr.setSelection(NodeSelection.create(tr.doc, replacements[0].from));
        }

        instance.view.dispatch(tr);
        fieldtype.update(stringify(instance.getJSON()));
    });

    return true;
}

onMounted(() => {
    editor.value = markRaw(new Editor({
        element: editorEl.value,
        extensions: [
            SingleLineDoc,
            Paragraph,
            Text,
            SingleLine,
            History,
            Placeholder.configure({ placeholder: __('advanced-seo::messages.token_picker_placeholder') }),
            Dropcursor,
            TokenNode.configure({ tokens: tokens.value }),
            TokenSelectionHighlight,
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
        content: parse(props.value),
        editable: !fieldtype.isReadOnly.value,
        editorProps: {
            attributes: {
                'data-antlers-input': '',
                class: 'min-w-full text-gray-925 dark:text-gray-300 antialiased text-base leading-[1.375rem] whitespace-nowrap outline-none',
            },
            handleKeyDown: (_view, event) => {
                if (event.key === '/' && suggestionState.value) return true;

                if (event.key === 'Escape' && suggestionState.value) {
                    editor.value?.commands.deleteRange(suggestionState.value.range);
                    return true;
                }

                if (event.key === 'Tab' && suggestionState.value) {
                    editor.value?.commands.deleteRange(suggestionState.value.range);
                    return false;
                }

                if ((event.key === 'Escape' || event.key === 'Enter') && !suggestionState.value && editor.value) {
                    if (collapseRemainingAntlers(editor.value, { selectCollapsed: true })) return true;
                }

                return false;
            },
        },
        onUpdate: ({ editor }) => {
            if (isInternalUpdate.value) return;

            nextTick(() => {
                if (suggestionState.value) return;

                withInternalUpdate(() => {
                    fieldtype.update(stringify(editor.getJSON()));
                });
            });
        },
        onSelectionUpdate: ({ editor: instance }) => {
            if (isInternalUpdate.value || suggestionState.value) return;

            const { selection } = instance.state;

            if (!selection.empty) return;

            const { $from } = selection;
            let hasAntlersText = false;
            let insideExpression = false;

            $from.parent.forEach((child, offset) => {
                if (insideExpression || !child.isText) return;
                if (!antlersPatternGlobal.test(child.text)) return;

                antlersPatternGlobal.lastIndex = 0;
                hasAntlersText = true;

                const cursorOffset = $from.parentOffset - offset;
                if (cursorOffset <= 0 || cursorOffset >= child.nodeSize) return;

                for (const match of child.text.matchAll(antlersPatternGlobal)) {
                    if (cursorOffset > match.index && cursorOffset < match.index + match[0].length) {
                        insideExpression = true;
                        break;
                    }
                }
            });

            if (hasAntlersText && !insideExpression) {
                collapseRemainingAntlers(instance);
            }
        },
        onFocus: () => {
            isEditorFocused.value = true;
            emit('focus');
        },
        onBlur: ({ editor: instance }) => {
            isEditorFocused.value = false;

            if (suggestionState.value) {
                instance.commands.deleteRange(suggestionState.value.range);
            }

            collapseRemainingAntlers(instance);
            emit('blur');
        },
    }));
});

onBeforeUnmount(() => editor.value?.destroy());

// ─── Watchers ───────────────────────────────────────────────────────────────

watch(() => props.value, (value) => {
    if (!editor.value || suggestionState.value) return;

    const current = stringify(editor.value.getJSON());

    if (value !== current) {
        withInternalUpdate(() => {
            editor.value.commands.setContent(parse(value));
        });
    }
}, { flush: 'post' });
</script>

<template>
    <div class="relative">
        <div class="relative">
            <div
                class="border border-gray-300 rounded-lg appearance-none dark:border-gray-700 shadow-ui-sm min-h-11"
                data-ui-input
                :data-suggestion-empty="(suggestionState && !suggestionState.query) || undefined"
                :style="{ '--suggestion-placeholder': `'${__('advanced-seo::messages.token_suggestion_placeholder')}'` }"
                :class="{
                    'pe-9': !fieldtype.isReadOnly.value,
                    'border-dashed pointer-events-none': fieldtype.isReadOnly.value,
                    'animate-pulse pointer-events-none bg-gray-100 dark:bg-gray-800': loading,
                    'bg-white dark:bg-gray-900': !loading,
                }"
            >
                <div class="px-3 py-2.5 overflow-x-auto [scrollbar-width:none]">
                    <div ref="editorEl" />
                </div>

                <TokenSuggestionList
                    v-if="suggestionState && isEditorFocused"
                    ref="suggestionListEl"
                    :items="items"
                />
            </div>

            <div v-if="!fieldtype.isReadOnly.value && !loading" class="absolute top-0 right-1.5 flex items-center h-11">
                <Button v-tooltip="__('Add Token')" round icon="plus" size="xs" icon-only :aria-label="__('Add Token')" @mousedown.prevent @click="openTokenSuggestion" />
            </div>
        </div>

        <CharacterCounter v-if="characterLimit && !fieldtype.isReadOnly.value" :count="characterCount.count" :limit="characterLimit" :approximate="characterCount.approximate" />
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
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius-sm);
    box-shadow: var(--ui-shadow-sm);
    color: var(--color-gray-700);
    font-size: var(--text-xs);
    line-height: 1.25rem;
    padding: 0 0.5rem;
}

:is(.dark) [data-antlers-input].ProseMirror-focused .suggestion-active {
    background-color: var(--color-gray-800);
    border: none;
    line-height: 1.375rem;
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
