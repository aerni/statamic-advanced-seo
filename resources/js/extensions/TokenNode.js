import { InputRule, Node, PasteRule } from '@tiptap/core';
import { NodeSelection } from '@tiptap/pm/state';
import { ANTLERS_PATTERN } from '../utils/antlers.js';

const TOKEN_BASE = 'inline-flex items-center justify-center ring-1 ring-inset font-normal antialiased whitespace-nowrap text-xs leading-[1.375rem] px-2 rounded-sm select-none cursor-grab align-top';
const TOKEN_VALID = 'bg-blue-50 ring-blue-300 text-blue-700 dark:bg-gray-800 dark:ring-blue-700 dark:text-blue-300';
const TOKEN_INVALID = 'bg-red-50 ring-red-400 text-red-700 dark:bg-gray-800 dark:ring-red-700 dark:text-red-300';

function resolveToken(handle, fields) {
    const field = fields.find(field => field.handle === handle);
    const display = field?.display || handle;
    const valid = Boolean(field);

    const attrs = {
        'data-token': handle,
        'data-token-valid': valid ? '' : undefined,
        'data-token-invalid': valid ? undefined : '',
        'class': `${TOKEN_BASE} ${valid ? TOKEN_VALID : TOKEN_INVALID}`,
    };

    return { attrs, display };
}

// Select node on mousedown so ProseMirror handles drag instead of the browser.
function selectNode(event, editor, getPos) {
    if (event.button !== 0) return;

    editor.view.focus();

    const { state, dispatch } = editor.view;
    const selection = NodeSelection.create(state.doc, getPos());
    dispatch(state.tr.setSelection(selection));
}

export const TokenNode = Node.create({
    name: 'token',
    inline: true,
    group: 'inline',
    atom: true,
    selectable: true,
    draggable: true,

    addOptions() {
        return {
            fields: [],
        };
    },

    addAttributes() {
        return {
            handle: {
                default: null,
                parseHTML: (element) => element.dataset.token,
            },
        };
    },

    parseHTML() {
        return [{
            tag: 'span[data-token]',
        }];
    },

    renderHTML({ node }) {
        const { attrs, display } = resolveToken(node.attrs.handle, this.options.fields);

        return ['span', attrs, display];
    },

    addNodeView() {
        return ({ node, getPos, editor }) => {
            const { attrs, display } = resolveToken(node.attrs.handle, this.options.fields);

            const dom = document.createElement('span');
            dom.textContent = display;

            Object.entries(attrs)
                .filter(([key, value]) => value !== undefined)
                .forEach(([key, value]) => dom.setAttribute(key, value));

            dom.addEventListener('mousedown', (event) => selectNode(event, editor, getPos));

            return { dom };
        };
    },

    addCommands() {
        return {
            insertToken: (handle) => ({ chain }) => {
                return chain().insertContent([
                    { type: this.name, attrs: { handle } },
                    { type: 'text', text: ' ' },
                ]).run();
            },
        };
    },

    addInputRules() {
        return [
            new InputRule({
                // Matches {{ handle }} followed by a trailing space to trigger token creation while typing.
                find: new RegExp(ANTLERS_PATTERN.source + '\\s$'),
                handler: ({ state, range, match }) => {
                    const handle = match[1];
                    const token = this.type.create({ handle });
                    const space = state.schema.text(' ');

                    state.tr.replaceWith(range.from, range.to, [token, space]);
                },
            }),
        ];
    },

    addPasteRules() {
        return [
            new PasteRule({
                // Matches all {{ handle }} occurrences in pasted text to convert them into tokens.
                find: new RegExp(ANTLERS_PATTERN.source, 'g'),
                handler: ({ state, range, match }) => {
                    const handle = match[1];
                    const token = this.type.create({ handle });

                    state.tr.replaceWith(range.from, range.to, token);
                },
            }),
        ];
    },
});
